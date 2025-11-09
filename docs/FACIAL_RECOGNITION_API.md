# Facial Recognition API Documentation

## Overview

This API provides endpoints for facial recognition onboarding and verification. Users must first complete onboarding by uploading 5 reference images. Once onboarded, they can verify their identity by submitting a photo for facial matching.

**Base URL:** `https://your-domain.com/api`

**Authentication:** All endpoints require Sanctum bearer token authentication.

---

## Authentication

Include the Sanctum token in the `Authorization` header:

```
Authorization: Bearer {your-token-here}
```

---

## Using the User Model (Recommended)

For Vue 3/TypeScript frontend, use the built-in `User` model methods which already handle authentication via Orion:

```typescript
import { User } from '@/models/User';

// Check onboarding status
const status = await User.getOnboardingStatus(userId);
// Returns: { has_reference_images: boolean, reference_images_count: number, onboarding_complete: boolean }

// Get reference images
const images = await User.getReferenceImages(userId);
// Returns: { images: Array, count: number }

// Upload reference images
const formData = new FormData();
imageFiles.forEach(file => formData.append('images[]', file));
const result = await User.uploadReferenceImages(userId, formData);

// Delete reference images
await User.deleteReferenceImages(userId);

// Verify face
const formData = new FormData();
formData.append('image', imageFile);
const verificationResult = await User.verifyFace(userId, formData);
// Returns: { verified: boolean, message: string, user_id?: number, error?: string }
```

**Note:** These methods use `Orion.makeHttpClient()` which automatically includes authentication headers. No need to manually add bearer tokens.

---

## Endpoints

### 1. Check Onboarding Status

Check if a user has completed facial recognition onboarding.

**Endpoint:** `GET /api/users/{userId}/onboarding/status`

**URL Parameters:**
- `userId` (integer, required) - The ID of the user

**Response (200 OK):**
```json
{
  "has_reference_images": true,
  "reference_images_count": 5,
  "onboarding_complete": true
}
```

**Response Fields:**
- `has_reference_images` (boolean) - True if user has exactly 5 reference images
- `reference_images_count` (integer) - Number of reference images uploaded (0-5)
- `onboarding_complete` (boolean) - True if onboarding is complete (has 5 images)

**Example Request (cURL):**
```bash
curl -X GET "https://your-domain.com/api/users/123/onboarding/status" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Example Request (JavaScript):**
```javascript
const response = await fetch(`${API_URL}/users/${userId}/onboarding/status`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
const data = await response.json();
```

---

### 2. List Reference Images

Get metadata about a user's reference images.

**Endpoint:** `GET /api/users/{userId}/onboarding`

**URL Parameters:**
- `userId` (integer, required) - The ID of the user

**Response (200 OK):**
```json
{
  "images": [
    {
      "id": 1,
      "order": 1,
      "created_at": "2025-11-08T15:17:18.000000Z",
      "has_file": true
    },
    {
      "id": 2,
      "order": 2,
      "created_at": "2025-11-08T15:17:19.000000Z",
      "has_file": true
    }
    // ... 3 more
  ],
  "count": 5
}
```

**Response Fields:**
- `images` (array) - Array of reference image metadata
  - `id` (integer) - Database ID of the image record
  - `order` (integer) - Order position (1-5)
  - `created_at` (string) - ISO 8601 timestamp
  - `has_file` (boolean) - True if physical file exists on disk
- `count` (integer) - Total number of reference images

**Example Request (JavaScript):**
```javascript
const response = await fetch(`${API_URL}/users/${userId}/onboarding`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
const data = await response.json();
```

---

### 3. Upload Reference Images (Onboarding)

Upload exactly 5 reference images for facial recognition onboarding. This can only be done once per user unless existing images are deleted first.

**Endpoint:** `POST /api/users/{userId}/onboarding`

**URL Parameters:**
- `userId` (integer, required) - The ID of the user

**Request Body (multipart/form-data):**
- `images[]` (file, required) - Array of exactly 5 image files
  - Format: JPEG, JPG, or PNG
  - Max size: 10MB per image
  - Must upload exactly 5 images

**Response (201 Created):**
```json
{
  "message": "Reference images uploaded successfully",
  "images": [
    {
      "id": 1,
      "user_id": 123,
      "image_path": "/path/to/storage/app/user-references/123/abc123.jpg",
      "order": 1,
      "created_at": "2025-11-08T15:17:18.000000Z",
      "updated_at": "2025-11-08T15:17:18.000000Z"
    }
    // ... 4 more images
  ]
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "The images field must have 5 items.",
  "errors": {
    "images": [
      "You must upload exactly 5 reference images."
    ]
  }
}
```

**Error Response (422 - Already Onboarded):**
```json
{
  "message": "Reference images have already been uploaded.",
  "errors": {
    "images": [
      "Reference images have already been uploaded. Please delete existing images first."
    ]
  }
}
```

**Example Request (cURL):**
```bash
curl -X POST "https://your-domain.com/api/users/123/onboarding" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -F "images[]=@/path/to/image1.jpg" \
  -F "images[]=@/path/to/image2.jpg" \
  -F "images[]=@/path/to/image3.jpg" \
  -F "images[]=@/path/to/image4.jpg" \
  -F "images[]=@/path/to/image5.jpg"
```

**Example Request (JavaScript with FormData):**
```javascript
const formData = new FormData();

// Add exactly 5 image files
for (let i = 0; i < 5; i++) {
  formData.append('images[]', imageFiles[i]);
}

const response = await fetch(`${API_URL}/users/${userId}/onboarding`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  },
  body: formData
});

const data = await response.json();
```

**Example Request (Vue 3 with User Model - Recommended):**
```vue
<script setup>
import { User } from '@/models/User';
import { ref } from 'vue';

const userId = ref(1); // Get from auth context
const images = ref([]);
const uploading = ref(false);

const handleFileUpload = (event) => {
  images.value = Array.from(event.target.files).slice(0, 5);
};

const uploadImages = async () => {
  if (images.value.length !== 5) {
    alert('Please select exactly 5 images');
    return;
  }

  uploading.value = true;

  const formData = new FormData();
  images.value.forEach(image => {
    formData.append('images[]', image);
  });

  try {
    const response = await User.uploadReferenceImages(userId.value, formData);
    console.log('Success:', response);
    // Navigate to next step
  } catch (error) {
    console.error('Error:', error.response?.data || error);
  } finally {
    uploading.value = false;
  }
};
</script>

<template>
  <div>
    <input
      type="file"
      multiple
      accept="image/jpeg,image/jpg,image/png"
      @change="handleFileUpload"
    />
    <p>Selected: {{ images.length }} / 5 images</p>
    <button
      @click="uploadImages"
      :disabled="images.length !== 5 || uploading"
    >
      {{ uploading ? 'Uploading...' : 'Upload Images' }}
    </button>
  </div>
</template>
```

---

### 4. Delete Reference Images

Delete all reference images for a user. This allows re-onboarding.

**Endpoint:** `DELETE /api/users/{userId}/onboarding`

**URL Parameters:**
- `userId` (integer, required) - The ID of the user

**Response (200 OK):**
```json
{
  "message": "All reference images deleted successfully"
}
```

**Example Request (cURL):**
```bash
curl -X DELETE "https://your-domain.com/api/users/123/onboarding" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Example Request (JavaScript):**
```javascript
const response = await fetch(`${API_URL}/users/${userId}/onboarding`, {
  method: 'DELETE',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const data = await response.json();
```

---

### 5. Verify User Face

Verify a user's identity by comparing a submitted photo against their reference images.

**Endpoint:** `POST /api/users/{userId}/verify`

**URL Parameters:**
- `userId` (integer, required) - The ID of the user to verify

**Request Body (multipart/form-data):**
- `image` (file, required) - Photo to verify
  - Format: JPEG, JPG, or PNG
  - Max size: 10MB

**Response (200 OK - Verified):**
```json
{
  "verified": true,
  "message": "Face verified successfully",
  "user_id": 123
}
```

**Response (401 Unauthorized - Verification Failed):**
```json
{
  "verified": false,
  "message": "Face verification failed",
  "error": "VERIFICATION_FAILED"
}
```

**Response (400 Bad Request - Not Onboarded):**
```json
{
  "verified": false,
  "message": "User has not completed facial recognition onboarding",
  "error": "ONBOARDING_INCOMPLETE"
}
```

**Response (422 Unprocessable Entity - Validation Error):**
```json
{
  "message": "The image field is required.",
  "errors": {
    "image": [
      "Please provide an image for verification."
    ]
  }
}
```

**Response (500 Internal Server Error):**
```json
{
  "verified": false,
  "message": "An error occurred during verification",
  "error": "INTERNAL_ERROR"
}
```

**Error Codes:**
- `VERIFICATION_FAILED` - Face did not match or confidence too low (<90%)
- `ONBOARDING_INCOMPLETE` - User has not uploaded 5 reference images
- `VERIFICATION_ERROR` - Error from facial recognition service
- `INTERNAL_ERROR` - Unexpected server error

**Example Request (cURL):**
```bash
curl -X POST "https://your-domain.com/api/users/123/verify" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -F "image=@/path/to/selfie.jpg"
```

**Example Request (JavaScript):**
```javascript
const formData = new FormData();
formData.append('image', imageFile);

const response = await fetch(`${API_URL}/users/${userId}/verify`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  },
  body: formData
});

const data = await response.json();

if (data.verified) {
  // Verification successful - grant access
  console.log('User verified!');
} else {
  // Verification failed
  console.error('Verification failed:', data.message);
}
```

**Example Request (TypeScript with User Model - Recommended):**
```typescript
import { User } from '@/models/User';

// Create form data with the image
const formData = new FormData();
formData.append('image', imageFile);

// Verify the face
const result = await User.verifyFace(userId, formData);

if (result.verified) {
  // Verification successful - grant access
  console.log('User verified!', result.user_id);
  // Navigate to app
} else {
  // Verification failed
  console.error('Verification failed:', result.message, result.error);
  // Show error to user
}
```

**Example Request (Vue 3 Component with User Model - Recommended):**
```vue
<script setup>
import { User } from '@/models/User';
import { ref } from 'vue';

const userId = ref(1); // Get from auth context
const imageFile = ref(null);
const verifying = ref(false);
const result = ref(null);

const handleFileChange = (event) => {
  imageFile.value = event.target.files[0];
};

const verifyFace = async () => {
  if (!imageFile.value) return;

  verifying.value = true;
  result.value = null;

  const formData = new FormData();
  formData.append('image', imageFile.value);

  try {
    const response = await User.verifyFace(userId.value, formData);
    result.value = response;

    if (response.verified) {
      // Handle successful verification
      console.log('Verification successful!');
      // Navigate to authenticated area
    }
  } catch (error) {
    if (error.response) {
      result.value = error.response.data;
      console.error('Verification failed:', error.response.data);
    }
  } finally {
    verifying.value = false;
  }
};
</script>

<template>
  <div>
    <input
      type="file"
      accept="image/jpeg,image/jpg,image/png"
      @change="handleFileChange"
    />

    <button
      @click="verifyFace"
      :disabled="!imageFile || verifying"
    >
      {{ verifying ? 'Verifying...' : 'Verify Face' }}
    </button>

    <div v-if="result">
      <div v-if="result.verified" class="success">
        ✓ {{ result.message }}
      </div>
      <div v-else class="error">
        ✗ {{ result.message }}
      </div>
    </div>
  </div>
</template>
```

---

## Verification Flow

The facial recognition system uses the **DIDIT API** for face matching with the following criteria:

- **Status:** Must be "Approved"
- **Confidence Score:** Must be greater than 90%

### How It Works

1. User uploads 5 reference images during onboarding
2. Images are stored securely in `storage/app/user-references/{userId}/`
3. When verifying, the system:
   - Randomly selects one of the 5 reference images
   - Sends both the reference image and the verification image to DIDIT API
   - DIDIT API returns a status ("Approved", "Rejected", etc.) and confidence score (0-100)
   - Returns `verified: true` only if status is "Approved" AND score > 90%

---

## Implementation Workflow

### Onboarding Flow

```
1. Check onboarding status
   GET /api/users/{userId}/onboarding/status

2. If not onboarded, collect 5 photos from user
   - Use camera or file upload
   - Ensure good lighting and clear face visibility
   - Recommend different angles/expressions

3. Upload reference images
   POST /api/users/{userId}/onboarding

4. Confirm success and mark user as onboarded
```

### Verification Flow

```
1. Check user is onboarded
   GET /api/users/{userId}/onboarding/status

2. Capture or upload verification photo
   - Use device camera for live capture (recommended)
   - Ensure good lighting and clear face

3. Submit for verification
   POST /api/users/{userId}/verify

4. Handle response:
   - verified: true → Grant access
   - verified: false → Show error, allow retry
```

---

## Best Practices

### Image Quality Guidelines

For best verification results, images should:
- Be well-lit with no harsh shadows
- Show the full face clearly
- Have the face centered in frame
- Be taken from a frontal angle (for reference images, variety is good)
- Be in focus and not blurry
- Have a neutral or simple background

### Reference Image Recommendations

When collecting the 5 reference images:
- Capture from slightly different angles (front, slight left, slight right)
- Use different expressions (neutral, smile)
- Ensure consistent lighting across all images
- Take images on the same day if possible

### Error Handling

Always handle these cases:
- Network errors (timeout, connection lost)
- Validation errors (wrong format, file too large)
- Verification failures (face doesn't match)
- Onboarding not complete
- Server errors

### Security Considerations

- Never expose the actual image file paths in the frontend
- Always validate file types and sizes on client-side before upload
- Implement rate limiting for verification attempts
- Log verification attempts for security auditing
- Consider implementing 2FA alongside facial recognition

---

## HTTP Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully (onboarding upload)
- `400 Bad Request` - Client error (e.g., user not onboarded)
- `401 Unauthorized` - Authentication failed or verification failed
- `404 Not Found` - User not found
- `422 Unprocessable Entity` - Validation error
- `500 Internal Server Error` - Server error

---

## Rate Limiting

All endpoints are subject to Laravel's default rate limiting (60 requests per minute per IP). Consider implementing stricter limits for verification endpoints to prevent abuse:

```javascript
// Recommended: Limit verification attempts
const MAX_VERIFICATION_ATTEMPTS = 3;
const LOCKOUT_DURATION = 300000; // 5 minutes in ms
```

---

## Testing

### Test Data

For development/testing, you can use test images or mock the DIDIT API responses.

### Example Test Flow

```javascript
// 1. Test onboarding status
const statusResponse = await fetch(`${API_URL}/users/1/onboarding/status`, {
  headers: { 'Authorization': `Bearer ${token}` }
});
console.log(await statusResponse.json());

// 2. Upload test images (if not onboarded)
// ... (see upload example above)

// 3. Test verification
const verifyResponse = await fetch(`${API_URL}/users/1/verify`, {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${token}` },
  body: testFormData
});
console.log(await verifyResponse.json());
```

---

## Support

For issues or questions, contact the backend team or refer to:
- API Source: `app/Http/Controllers/Api/FacialOnboardingController.php`
- API Source: `app/Http/Controllers/Api/FacialVerificationController.php`
- Service: `app/Services/FaceMatchService.php`
- DIDIT Integration: `app/Services/DidItService.php`
