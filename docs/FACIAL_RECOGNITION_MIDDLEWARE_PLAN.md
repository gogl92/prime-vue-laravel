# Facial Recognition Middleware Implementation Plan

## Overview

This document outlines the implementation plan for requiring facial recognition verification on sensitive API endpoints. The system will intercept protected endpoints, require users to verify their face, and only allow the request to proceed if verification succeeds.

---

## Architecture

### Flow Diagram

```
User Request → Endpoint → Middleware → Check if FR Required
                                ↓
                        Generate Verification Token
                                ↓
                        Return 403 + Token
                                ↓
Frontend Shows Verification Modal
                                ↓
User Submits Face Photo + Token
                                ↓
Verification API Validates Face
                                ↓
If Valid: Store Verification Session
                                ↓
Frontend Retries Original Request
                                ↓
Middleware Checks Verification Session
                                ↓
Request Proceeds Successfully
```

---

## Backend Implementation

### 1. Database Migration: Verification Sessions

Create a table to track active facial recognition sessions:

**Migration:** `create_facial_verification_sessions_table.php`

```php
Schema::create('facial_verification_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('token')->unique(); // UUID token
    $table->string('ip_address');
    $table->string('user_agent')->nullable();
    $table->timestamp('verified_at')->nullable();
    $table->timestamp('expires_at');
    $table->timestamps();

    $table->index(['user_id', 'verified_at']);
    $table->index(['token', 'expires_at']);
});
```

**Fields:**
- `token`: Unique UUID to link verification request to completion
- `verified_at`: Timestamp when face was verified (null = pending)
- `expires_at`: Token expiration (5 minutes)
- `ip_address` & `user_agent`: Security tracking

---

### 2. Model: FacialVerificationSession

**File:** `app/Models/FacialVerificationSession.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacialVerificationSession extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'ip_address',
        'user_agent',
        'verified_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }
}
```

---

### 3. Middleware: RequireFacialVerification

**File:** `app/Http/Middleware/RequireFacialVerification.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\FacialVerificationSession;

class RequireFacialVerification
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if user has completed onboarding
        if ($user->referenceImages()->count() !== 5) {
            return response()->json([
                'error' => 'FACIAL_RECOGNITION_NOT_CONFIGURED',
                'message' => 'Facial recognition is not configured for this account',
            ], 403);
        }

        // Check if request has a valid verification token
        $verificationToken = $request->header('X-FR-Verification-Token');

        if ($verificationToken) {
            $session = FacialVerificationSession::where('token', $verificationToken)
                ->where('user_id', $user->id)
                ->first();

            if ($session && $session->isVerified() && !$session->isExpired()) {
                // Valid verification - allow request to proceed
                return $next($request);
            }
        }

        // No valid verification - create new session and require verification
        $token = Str::uuid()->toString();

        FacialVerificationSession::create([
            'user_id' => $user->id,
            'token' => $token,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addMinutes(5),
        ]);

        return response()->json([
            'error' => 'FACIAL_VERIFICATION_REQUIRED',
            'message' => 'Facial verification is required to proceed',
            'verification_token' => $token,
            'expires_in' => 300, // seconds
        ], 403);
    }
}
```

**Register in** `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'facial.verify' => \App\Http\Middleware\RequireFacialVerification::class,
    ]);
})
```

---

### 4. Verification Completion Endpoint

**Controller:** `app/Http/Controllers/Api/FacialVerificationController.php`

Add a new method:

```php
/**
 * Complete facial verification session.
 * POST /api/facial-verification/complete
 */
public function complete(Request $request): JsonResponse
{
    $request->validate([
        'verification_token' => 'required|string',
        'image' => 'required|image|mimes:jpeg,jpg,png|max:10240',
    ]);

    $user = $request->user();

    // Find the verification session
    $session = FacialVerificationSession::where('token', $request->verification_token)
        ->where('user_id', $user->id)
        ->first();

    if (!$session) {
        return response()->json([
            'verified' => false,
            'message' => 'Invalid verification token',
            'error' => 'INVALID_TOKEN',
        ], 400);
    }

    if ($session->isExpired()) {
        return response()->json([
            'verified' => false,
            'message' => 'Verification token has expired',
            'error' => 'TOKEN_EXPIRED',
        ], 400);
    }

    if ($session->isVerified()) {
        return response()->json([
            'verified' => true,
            'message' => 'Already verified',
            'verification_token' => $session->token,
        ]);
    }

    // Store uploaded image temporarily
    $uploadedImage = $request->file('image');
    $tempPath = $uploadedImage->store('temp-verification', 'local');
    $fullPath = storage_path("app/{$tempPath}");

    try {
        // Perform facial verification
        $isMatch = $this->faceMatchService->match($user, $fullPath);

        // Clean up temp file
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        if ($isMatch) {
            // Mark session as verified
            $session->markAsVerified();

            return response()->json([
                'verified' => true,
                'message' => 'Face verified successfully',
                'verification_token' => $session->token,
            ]);
        }

        return response()->json([
            'verified' => false,
            'message' => 'Face verification failed',
            'error' => 'VERIFICATION_FAILED',
        ], 401);

    } catch (\Exception $e) {
        // Clean up temp file
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        return response()->json([
            'verified' => false,
            'message' => $e->getMessage(),
            'error' => 'VERIFICATION_ERROR',
        ], 500);
    }
}
```

**Route:** `routes/api.php`
```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('facial-verification/complete', [FacialVerificationController::class, 'complete']);
});
```

---

### 5. Usage on Protected Endpoints

Apply the middleware to any endpoint that requires facial verification:

```php
// Single endpoint
Route::post('sensitive-data', [SomeController::class, 'store'])
    ->middleware(['auth:sanctum', 'facial.verify']);

// Group of endpoints
Route::middleware(['auth:sanctum', 'facial.verify'])->group(function () {
    Route::post('financial/withdraw', [FinancialController::class, 'withdraw']);
    Route::post('settings/delete-account', [SettingsController::class, 'deleteAccount']);
    Route::post('admin/critical-action', [AdminController::class, 'criticalAction']);
});
```

---

## Frontend Implementation

### 1. Axios Interceptor (Auto-Detection)

**File:** `resources/js/services/facialVerificationInterceptor.ts`

```typescript
import { Orion } from '@tailflow/laravel-orion/lib/orion';
import { showFacialVerificationModal } from './facialVerificationModal';

let isModalOpen = false;

// Response interceptor
Orion.makeHttpClient().interceptors.response.use(
  response => response,
  async error => {
    const { response } = error;

    // Check if facial verification is required
    if (response?.status === 403 && response?.data?.error === 'FACIAL_VERIFICATION_REQUIRED') {
      const { verification_token } = response.data;

      // Prevent multiple modals
      if (isModalOpen) {
        return Promise.reject(error);
      }

      isModalOpen = true;

      try {
        // Show verification modal and wait for completion
        const verified = await showFacialVerificationModal(verification_token);

        if (verified) {
          // Retry the original request with the verification token
          const originalRequest = error.config;
          originalRequest.headers['X-FR-Verification-Token'] = verification_token;

          return Orion.makeHttpClient()(originalRequest);
        }
      } finally {
        isModalOpen = false;
      }
    }

    return Promise.reject(error);
  }
);
```

---

### 2. Verification Modal Component

**File:** `resources/js/components/FacialVerificationModal.vue`

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { User } from '@/models/User';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Message from 'primevue/message';

interface Props {
  visible: boolean;
  verificationToken: string;
  userId: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'update:visible': [value: boolean];
  'verified': [token: string];
  'failed': [];
}>();

const videoElement = ref<HTMLVideoElement | null>(null);
const videoStream = ref<MediaStream | null>(null);
const capturedImage = ref<string | null>(null);
const isCameraActive = ref(false);
const verifying = ref(false);
const error = ref<string | null>(null);

const openCamera = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: 640, height: 480 }
    });
    videoStream.value = stream;

    await new Promise(resolve => setTimeout(resolve, 100));

    if (videoElement.value) {
      videoElement.value.srcObject = stream;
      isCameraActive.value = true;
    }
  } catch (err) {
    error.value = 'Failed to access camera. Please check permissions.';
  }
};

const capturePhoto = () => {
  if (!videoElement.value) return;

  const canvas = document.createElement('canvas');
  canvas.width = videoElement.value.videoWidth;
  canvas.height = videoElement.value.videoHeight;

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  ctx.drawImage(videoElement.value, 0, 0);
  capturedImage.value = canvas.toDataURL('image/jpeg', 0.9);
  isCameraActive.value = false;
};

const retake = () => {
  capturedImage.value = null;
  error.value = null;
  isCameraActive.value = true;
};

const verifyFace = async () => {
  if (!capturedImage.value) return;

  try {
    verifying.value = true;
    error.value = null;

    // Convert base64 to blob
    const response = await fetch(capturedImage.value);
    const blob = await response.blob();
    const file = new File([blob], 'verification.jpg', { type: 'image/jpeg' });

    // Submit verification
    const formData = new FormData();
    formData.append('verification_token', props.verificationToken);
    formData.append('image', file);

    const result = await Orion.makeHttpClient().post('/api/facial-verification/complete', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (result.data.verified) {
      emit('verified', props.verificationToken);
      emit('update:visible', false);
    } else {
      error.value = result.data.message || 'Verification failed';
      capturedImage.value = null;
      isCameraActive.value = true;
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Verification error occurred';
    capturedImage.value = null;
    isCameraActive.value = true;
  } finally {
    verifying.value = false;
  }
};

const close = () => {
  if (videoStream.value) {
    videoStream.value.getTracks().forEach(track => track.stop());
  }
  emit('update:visible', false);
  emit('failed');
};

// Auto-open camera when modal opens
watch(() => props.visible, (newVal) => {
  if (newVal && !isCameraActive.value && !capturedImage.value) {
    openCamera();
  }
});
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :closable="false"
    :style="{ width: '90vw', maxWidth: '600px' }"
  >
    <template #header>
      <div class="flex items-center gap-2">
        <i class="pi pi-shield-check text-2xl text-orange-500"></i>
        <span class="font-semibold">Facial Verification Required</span>
      </div>
    </template>

    <div class="space-y-4">
      <Message severity="warn" :closable="false">
        This action requires facial verification for security. Please take a clear photo of your face.
      </Message>

      <Message v-if="error" severity="error" :closable="false">
        {{ error }}
      </Message>

      <!-- Camera Preview -->
      <div class="relative bg-black rounded-lg overflow-hidden" style="aspect-ratio: 4/3;">
        <video
          v-show="isCameraActive && !capturedImage"
          ref="videoElement"
          autoplay
          playsinline
          class="w-full h-full object-cover"
        />

        <img
          v-if="capturedImage"
          :src="capturedImage"
          alt="Captured photo"
          class="w-full h-full object-cover"
        />

        <div
          v-if="verifying"
          class="absolute inset-0 bg-black bg-opacity-70 flex items-center justify-center"
        >
          <div class="text-center text-white">
            <i class="pi pi-spin pi-spinner text-4xl mb-3"></i>
            <div>Verifying...</div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div v-if="!capturedImage" class="flex justify-between gap-2">
        <Button
          label="Cancel"
          icon="pi pi-times"
          severity="secondary"
          @click="close"
        />
        <Button
          label="Capture"
          icon="pi pi-camera"
          severity="success"
          :disabled="!isCameraActive"
          @click="capturePhoto"
        />
      </div>

      <div v-else class="flex justify-between gap-2">
        <Button
          label="Retake"
          icon="pi pi-replay"
          severity="secondary"
          @click="retake"
        />
        <Button
          label="Verify"
          icon="pi pi-check"
          severity="success"
          :loading="verifying"
          @click="verifyFace"
        />
      </div>
    </template>
  </Dialog>
</template>
```

---

### 3. Global Modal Service

**File:** `resources/js/services/facialVerificationModal.ts`

```typescript
import { createApp, h } from 'vue';
import FacialVerificationModal from '@/components/FacialVerificationModal.vue';
import PrimeVue from 'primevue/config';
import NoirPreset from '@/theme/noir-preset';

export function showFacialVerificationModal(verificationToken: string): Promise<boolean> {
  return new Promise((resolve) => {
    const container = document.createElement('div');
    document.body.appendChild(container);

    const app = createApp({
      setup() {
        const visible = ref(true);
        const userId = usePage().props.auth.user?.id;

        const handleVerified = (token: string) => {
          cleanup();
          resolve(true);
        };

        const handleFailed = () => {
          cleanup();
          resolve(false);
        };

        const cleanup = () => {
          app.unmount();
          document.body.removeChild(container);
        };

        return () => h(FacialVerificationModal, {
          visible: visible.value,
          'onUpdate:visible': (val: boolean) => {
            visible.value = val;
            if (!val) handleFailed();
          },
          verificationToken,
          userId,
          onVerified: handleVerified,
          onFailed: handleFailed,
        });
      }
    });

    app.use(PrimeVue, { theme: { preset: NoirPreset } });
    app.mount(container);
  });
}
```

---

## Usage Examples

### Backend: Protect an Endpoint

```php
// Require facial verification for sensitive action
Route::post('financial/withdraw', [FinancialController::class, 'withdraw'])
    ->middleware(['auth:sanctum', 'facial.verify']);
```

### Frontend: Automatic Verification

```typescript
// User clicks "Withdraw" button
const withdraw = async () => {
  try {
    // This will automatically show verification modal if required
    const response = await Orion.makeHttpClient().post('/api/financial/withdraw', {
      amount: 1000,
      account: '123456'
    });

    // If we get here, verification passed and withdrawal succeeded
    toast.success('Withdrawal successful!');
  } catch (error) {
    toast.error('Withdrawal failed');
  }
};
```

The interceptor handles everything automatically!

---

## Security Considerations

1. **Token Expiration**: Verification tokens expire in 5 minutes
2. **One-Time Use**: Each verification is session-specific
3. **IP Validation**: Optional IP address matching
4. **Rate Limiting**: Add rate limiting to verification endpoint
5. **Audit Logging**: Log all verification attempts
6. **Cleanup**: Delete expired sessions regularly via scheduled command

---

## Database Cleanup Command

**File:** `app/Console/Commands/CleanExpiredVerificationSessions.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacialVerificationSession;

class CleanExpiredVerificationSessions extends Command
{
    protected $signature = 'facial:clean-sessions';
    protected $description = 'Clean expired facial verification sessions';

    public function handle()
    {
        $deleted = FacialVerificationSession::where('expires_at', '<', now())->delete();
        $this->info("Deleted {$deleted} expired verification sessions");
    }
}
```

**Schedule in** `routes/console.php`:
```php
Schedule::command('facial:clean-sessions')->hourly();
```

---

## Summary

✅ **Image Display Fixed**: Backend serves images via `/api/users/{userId}/onboarding/images/{imageId}`
✅ **Verification Widget**: Camera-based modal for face verification
✅ **Middleware System**: Protect any endpoint with `facial.verify` middleware
✅ **Automatic Flow**: Frontend interceptor handles verification automatically
✅ **Security**: Token-based sessions with expiration and validation

The system is fully planned and ready for implementation!
