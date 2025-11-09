<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { User } from '@/models/User';
import AppLayout from '@/layouts/AppLayout.vue';

// Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import FileUpload from 'primevue/fileupload';
import ProgressBar from 'primevue/progressbar';
import Message from 'primevue/message';
import Dialog from 'primevue/dialog';
import { Orion } from '@tailflow/laravel-orion/lib/orion';

// Props
interface Props {
  userId: number;
  user?: {
    id: number;
    username: string;
    first_name: string;
    last_name: string;
    email: string;
  };
}

const props = defineProps<Props>();

// Composables
const { t } = useI18n();
const toast = useToast();

// State
const loading = ref(false);
const uploading = ref(false);
const deleting = ref(false);
const uploadedImages = ref<File[]>([]);
const existingImages = ref<any[]>([]);
const onboardingStatus = ref<any>(null);
const showDeleteDialog = ref(false);
const showCameraDialog = ref(false);
const videoStream = ref<MediaStream | null>(null);
const videoElement = ref<HTMLVideoElement | null>(null);
const capturedImage = ref<string | null>(null);
const isCameraActive = ref(false);

// Verification state
const showVerificationCamera = ref(false);
const verificationVideoStream = ref<MediaStream | null>(null);
const verificationVideoElement = ref<HTMLVideoElement | null>(null);
const verificationCapturedImage = ref<string | null>(null);
const isVerificationCameraActive = ref(false);
const verifying = ref(false);
const verificationResult = ref<any>(null);
const showVerificationResult = ref(false);

// Computed
const canUpload = computed(() => uploadedImages.value.length === 5);
const hasExistingImages = computed(() => existingImages.value.length > 0);
const uploadProgress = computed(() =>
  Math.round((uploadedImages.value.length / 5) * 100)
);

// Methods
const loadOnboardingStatus = async () => {
  try {
    loading.value = true;
    const response = await User.getOnboardingStatus(props.userId);
    onboardingStatus.value = response.data;

    if (response.data.has_reference_images) {
      await loadExistingImages();
    }
  } catch (error) {
    console.error('Error loading onboarding status:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load onboarding status'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

const loadExistingImages = async () => {
  try {
    const response = await User.getReferenceImages(props.userId);
    existingImages.value = response.data.images;
  } catch (error) {
    console.error('Error loading existing images:', error);
  }
};

const onFileSelect = (event: any) => {
  const files = event.files;

  if (files.length + uploadedImages.value.length > 5) {
    toast.add({
      severity: 'warn',
      summary: t('Warning'),
      detail: t('You can only upload up to 5 images'),
      life: 3000,
    });
    return;
  }

  uploadedImages.value = [...uploadedImages.value, ...files].slice(0, 5);
};

const removeImage = (index: number) => {
  uploadedImages.value.splice(index, 1);
};

const uploadImages = async () => {
  if (uploadedImages.value.length !== 5) {
    toast.add({
      severity: 'warn',
      summary: t('Warning'),
      detail: t('Please select exactly 5 images'),
      life: 3000,
    });
    return;
  }

  try {
    uploading.value = true;
    const formData = new FormData();

    uploadedImages.value.forEach((file) => {
      formData.append('images[]', file);
    });

    await User.uploadReferenceImages(props.userId, formData);

    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('Reference images uploaded successfully'),
      life: 3000,
    });

    uploadedImages.value = [];
    await loadOnboardingStatus();
  } catch (error: any) {
    console.error('Error uploading images:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: error.$response?.data?.message || t('Failed to upload images'),
      life: 3000,
    });
  } finally {
    uploading.value = false;
  }
};

const confirmDeleteAll = () => {
  showDeleteDialog.value = true;
};

const deleteAllImages = async () => {
  try {
    deleting.value = true;
    await User.deleteReferenceImages(props.userId);

    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('All reference images deleted successfully'),
      life: 3000,
    });

    showDeleteDialog.value = false;
    existingImages.value = [];
    await loadOnboardingStatus();
  } catch (error) {
    console.error('Error deleting images:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to delete images'),
      life: 3000,
    });
  } finally {
    deleting.value = false;
  }
};

const goBack = () => {
  router.visit('/users');
};

const getImagePreview = (file: File): string => {
  return URL.createObjectURL(file);
};

const openCamera = async () => {
  showCameraDialog.value = true;
  capturedImage.value = null;

  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: 640, height: 480 }
    });
    videoStream.value = stream;

    // Wait for next tick to ensure video element is in DOM
    await new Promise(resolve => setTimeout(resolve, 100));

    if (videoElement.value) {
      videoElement.value.srcObject = stream;
      isCameraActive.value = true;
    }
  } catch (error) {
    console.error('Error accessing camera:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to access camera. Please check permissions.'),
      life: 3000,
    });
    showCameraDialog.value = false;
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

  // Stop the camera preview
  isCameraActive.value = false;
};

const retakePhoto = () => {
  capturedImage.value = null;
  isCameraActive.value = true;
};

const confirmPhoto = async () => {
  if (!capturedImage.value) return;

  try {
    // Convert base64 to blob
    const response = await fetch(capturedImage.value);
    const blob = await response.blob();

    // Create File object
    const timestamp = new Date().getTime();
    const file = new File([blob], `camera-capture-${timestamp}.jpg`, { type: 'image/jpeg' });

    // Add to uploaded images
    if (uploadedImages.value.length < 5) {
      uploadedImages.value.push(file);
      toast.add({
        severity: 'success',
        summary: t('Success'),
        detail: t('Photo added successfully'),
        life: 2000,
      });
      closeCamera();
    } else {
      toast.add({
        severity: 'warn',
        summary: t('Warning'),
        detail: t('You can only upload up to 5 images'),
        life: 3000,
      });
    }
  } catch (error) {
    console.error('Error converting photo:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to process photo'),
      life: 3000,
    });
  }
};

const closeCamera = () => {
  if (videoStream.value) {
    videoStream.value.getTracks().forEach(track => track.stop());
    videoStream.value = null;
  }
  isCameraActive.value = false;
  capturedImage.value = null;
  showCameraDialog.value = false;
};

// Verification Methods
const openVerificationCamera = async () => {
  showVerificationCamera.value = true;
  verificationCapturedImage.value = null;
  verificationResult.value = null;

  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: 640, height: 480 }
    });
    verificationVideoStream.value = stream;

    await new Promise(resolve => setTimeout(resolve, 100));

    if (verificationVideoElement.value) {
      verificationVideoElement.value.srcObject = stream;
      isVerificationCameraActive.value = true;
    }
  } catch (error) {
    console.error('Error accessing camera:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to access camera. Please check permissions.'),
      life: 3000,
    });
    showVerificationCamera.value = false;
  }
};

const captureVerificationPhoto = () => {
  if (!verificationVideoElement.value) return;

  const canvas = document.createElement('canvas');
  canvas.width = verificationVideoElement.value.videoWidth;
  canvas.height = verificationVideoElement.value.videoHeight;

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  ctx.drawImage(verificationVideoElement.value, 0, 0);
  verificationCapturedImage.value = canvas.toDataURL('image/jpeg', 0.9);
  isVerificationCameraActive.value = false;
};

const retakeVerificationPhoto = () => {
  verificationCapturedImage.value = null;
  verificationResult.value = null;
  isVerificationCameraActive.value = true;
};

const verifyPhoto = async () => {
  if (!verificationCapturedImage.value) return;

  try {
    verifying.value = true;

    // Convert base64 to blob
    const response = await fetch(verificationCapturedImage.value);
    const blob = await response.blob();

    // Create File object
    const file = new File([blob], 'verification.jpg', { type: 'image/jpeg' });

    // Submit for verification
    const formData = new FormData();
    formData.append('image', file);

    const verifyResponse = await User.verifyFace(props.userId, formData);

    verificationResult.value = verifyResponse.data;
    showVerificationResult.value = true;

    if (verifyResponse.data.verified) {
      toast.add({
        severity: 'success',
        summary: t('Success'),
        detail: t('Face verified successfully!'),
        life: 5000,
      });
    } else {
      toast.add({
        severity: 'error',
        summary: t('Verification Failed'),
        detail: verifyResponse.data.message || t('Face verification failed'),
        life: 5000,
      });
    }
  } catch (error: any) {
    console.error('Error verifying photo:', error);

    const errorData = error.$response?.data;
    verificationResult.value = errorData || {
      verified: false,
      message: t('An error occurred during verification'),
    };
    showVerificationResult.value = true;

    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: errorData?.message || t('Failed to verify face'),
      life: 5000,
    });
  } finally {
    verifying.value = false;
  }
};

const closeVerificationCamera = () => {
  if (verificationVideoStream.value) {
    verificationVideoStream.value.getTracks().forEach(track => track.stop());
    verificationVideoStream.value = null;
  }
  isVerificationCameraActive.value = false;
  verificationCapturedImage.value = null;
  verificationResult.value = null;
  showVerificationCamera.value = false;
  showVerificationResult.value = false;
};

const testAgain = () => {
  verificationCapturedImage.value = null;
  verificationResult.value = null;
  showVerificationResult.value = false;
  isVerificationCameraActive.value = true;
};

// Lifecycle
onMounted(() => {
  void loadOnboardingStatus();
});

// Cleanup on unmount
onBeforeUnmount(() => {
  if (videoStream.value) {
    videoStream.value.getTracks().forEach(track => track.stop());
  }
  if (verificationVideoStream.value) {
    verificationVideoStream.value.getTracks().forEach(track => track.stop());
  }
});
</script>

<template>
  <AppLayout :title="t('Facial Recognition Setup')">
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
            {{ t('Facial Recognition Setup') }}
          </h1>
          <p v-if="user" class="text-surface-600 dark:text-surface-400 mt-1">
            {{ user.first_name }} {{ user.last_name }} ({{ user.username }})
          </p>
        </div>
        <Button
          :label="t('Back')"
          icon="pi pi-arrow-left"
          severity="secondary"
          @click="goBack"
        />
      </div>

      <!-- Status Message -->
      <Message
        v-if="onboardingStatus?.onboarding_complete"
        severity="success"
        :closable="false"
      >
        {{ t('Facial recognition is fully configured with') }} {{ onboardingStatus.reference_images_count }} {{ t('images') }}.
      </Message>
      <Message
        v-else-if="onboardingStatus?.has_reference_images"
        severity="warn"
        :closable="false"
      >
        {{ t('Partial setup: Only') }} {{ onboardingStatus.reference_images_count }} {{ t('of 5 images uploaded') }}.
      </Message>
      <Message
        v-else
        severity="info"
        :closable="false"
      >
        {{ t('No reference images uploaded yet. Upload 5 images to complete setup') }}.
      </Message>

      <!-- Existing Images -->
      <Card v-if="hasExistingImages">
        <template #title>
          <div class="flex items-center justify-between">
            <span>{{ t('Current Reference Images') }}</span>
            <Button
              :label="t('Delete All')"
              icon="pi pi-trash"
              severity="danger"
              size="small"
              @click="confirmDeleteAll"
            />
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div
              v-for="image in existingImages"
              :key="image.id"
              class="relative"
            >
              <div class="aspect-square bg-surface-100 dark:bg-surface-800 rounded-lg flex items-center justify-center">
                <i class="pi pi-image text-4xl text-surface-400"></i>
              </div>
              <div class="text-center mt-2 text-sm text-surface-600 dark:text-surface-400">
                {{ t('Image') }} {{ image.order }}
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Upload New Images -->
      <Card>
        <template #title>{{ t('Upload Reference Images') }}</template>
        <template #subtitle>
          {{ t('Upload exactly 5 images for facial recognition. These images should show your face clearly from different angles') }}.
        </template>
        <template #content>
          <div class="space-y-4">
            <!-- Progress Bar -->
            <div v-if="uploadedImages.length > 0">
              <label class="block text-sm font-medium mb-2">
                {{ t('Upload Progress') }}: {{ uploadedImages.length }}/5 {{ t('images') }}
              </label>
              <ProgressBar :value="uploadProgress" />
            </div>

            <!-- File Upload & Camera -->
            <div class="flex gap-2">
              <FileUpload
                mode="basic"
                name="images[]"
                accept="image/jpeg,image/jpg,image/png"
                :max-file-size="5242880"
                :multiple="true"
                :auto="false"
                :choose-label="t('Choose Images')"
                :disabled="uploadedImages.length >= 5"
                @select="onFileSelect"
              />
              <Button
                :label="t('Open Camera')"
                icon="pi pi-camera"
                severity="info"
                :disabled="uploadedImages.length >= 5"
                @click="openCamera"
              />
            </div>

            <!-- Image Previews -->
            <div v-if="uploadedImages.length > 0" class="grid grid-cols-2 md:grid-cols-5 gap-4">
              <div
                v-for="(file, index) in uploadedImages"
                :key="index"
                class="relative group"
              >
                <img
                  :src="getImagePreview(file)"
                  :alt="`Preview ${index + 1}`"
                  class="w-full aspect-square object-cover rounded-lg"
                />
                <Button
                  icon="pi pi-times"
                  severity="danger"
                  rounded
                  size="small"
                  class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                  @click="removeImage(index)"
                />
                <div class="text-center mt-2 text-sm text-surface-600 dark:text-surface-400">
                  {{ t('Image') }} {{ index + 1 }}
                </div>
              </div>
            </div>

            <!-- Upload Button -->
            <div class="flex gap-2">
              <Button
                :label="t('Upload Images')"
                icon="pi pi-upload"
                :disabled="!canUpload"
                :loading="uploading"
                @click="uploadImages"
              />
              <Button
                v-if="uploadedImages.length > 0"
                :label="t('Clear All')"
                icon="pi pi-times"
                severity="secondary"
                @click="uploadedImages = []"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Instructions -->
      <Card>
        <template #title>{{ t('Instructions') }}</template>
        <template #content>
          <ul class="list-disc list-inside space-y-2 text-surface-700 dark:text-surface-300">
            <li>{{ t('Upload exactly 5 clear photos of your face') }}</li>
            <li>{{ t('Take photos from different angles (front, left, right, up, down)') }}</li>
            <li>{{ t('Ensure good lighting and avoid shadows on your face') }}</li>
            <li>{{ t('Remove glasses, hats, or any face coverings') }}</li>
            <li>{{ t('Each image must be in JPEG or PNG format and under 5MB') }}</li>
            <li>{{ t('These images will be used to authenticate you via facial recognition') }}</li>
          </ul>
        </template>
      </Card>

      <!-- Test Facial Recognition -->
      <Card v-if="onboardingStatus?.onboarding_complete">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-verified text-green-500"></i>
            <span>{{ t('Test Facial Recognition') }}</span>
          </div>
        </template>
        <template #subtitle>
          {{ t('Verify that your facial recognition setup is working correctly') }}.
        </template>
        <template #content>
          <div class="space-y-4">
            <Message severity="info" :closable="false">
              {{ t('Take a selfie to test if the system can recognize you. Make sure you have good lighting.') }}
            </Message>

            <Button
              :label="t('Test Recognition')"
              icon="pi pi-camera"
              severity="success"
              size="large"
              @click="openVerificationCamera"
            />
          </div>
        </template>
      </Card>
    </div>

    <!-- Delete Confirmation Dialog -->
    <Dialog
      v-model:visible="showDeleteDialog"
      :header="t('Confirm Delete')"
      modal
      :style="{ width: '400px' }"
    >
      <div class="flex items-center gap-3 mb-4">
        <i class="pi pi-exclamation-triangle text-orange-500 text-2xl" />
        <span>
          {{ t('Are you sure you want to delete all reference images? This action cannot be undone') }}.
        </span>
      </div>

      <template #footer>
        <Button :label="t('Cancel')" severity="secondary" @click="showDeleteDialog = false" />
        <Button :label="t('Delete All')" severity="danger" :loading="deleting" @click="deleteAllImages" />
      </template>
    </Dialog>

    <!-- Camera Capture Dialog -->
    <Dialog
      v-model:visible="showCameraDialog"
      :header="t('Capture Photo')"
      modal
      :style="{ width: '90vw', maxWidth: '700px' }"
      @hide="closeCamera"
    >
      <div class="space-y-4">
        <!-- Camera Preview or Captured Image -->
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
            v-if="!isCameraActive && !capturedImage"
            class="absolute inset-0 flex items-center justify-center"
          >
            <i class="pi pi-camera text-6xl text-gray-400"></i>
          </div>
        </div>

        <!-- Instructions -->
        <Message severity="info" :closable="false">
          {{ t('Position your face in the center and ensure good lighting') }}
        </Message>
      </div>

      <template #footer>
        <div v-if="!capturedImage" class="flex justify-center gap-2">
          <Button
            :label="t('Cancel')"
            icon="pi pi-times"
            severity="secondary"
            @click="closeCamera"
          />
          <Button
            :label="t('Capture')"
            icon="pi pi-camera"
            severity="success"
            :disabled="!isCameraActive"
            @click="capturePhoto"
          />
        </div>
        <div v-else class="flex justify-center gap-2">
          <Button
            :label="t('Retake')"
            icon="pi pi-replay"
            severity="secondary"
            @click="retakePhoto"
          />
          <Button
            :label="t('Use Photo')"
            icon="pi pi-check"
            severity="success"
            @click="confirmPhoto"
          />
        </div>
      </template>
    </Dialog>

    <!-- Verification Camera Dialog -->
    <Dialog
      v-model:visible="showVerificationCamera"
      :header="t('Test Facial Recognition')"
      modal
      :style="{ width: '90vw', maxWidth: '700px' }"
      @hide="closeVerificationCamera"
    >
      <div class="space-y-4">
        <!-- Verification Result -->
        <div v-if="showVerificationResult && verificationResult" class="mb-4">
          <Message
            v-if="verificationResult.verified"
            severity="success"
            :closable="false"
          >
            <div class="flex items-center gap-3">
              <i class="pi pi-check-circle text-3xl"></i>
              <div>
                <div class="font-semibold text-lg">{{ t('Verification Successful!') }}</div>
                <div class="text-sm">{{ t('Your face was recognized successfully') }}</div>
              </div>
            </div>
          </Message>
          <Message
            v-else
            severity="error"
            :closable="false"
          >
            <div class="flex items-center gap-3">
              <i class="pi pi-times-circle text-3xl"></i>
              <div>
                <div class="font-semibold text-lg">{{ t('Verification Failed') }}</div>
                <div class="text-sm">{{ verificationResult.message || t('Face could not be verified') }}</div>
              </div>
            </div>
          </Message>
        </div>

        <!-- Camera Preview or Captured Image -->
        <div class="relative bg-black rounded-lg overflow-hidden" style="aspect-ratio: 4/3;">
          <video
            v-show="isVerificationCameraActive && !verificationCapturedImage"
            ref="verificationVideoElement"
            autoplay
            playsinline
            class="w-full h-full object-cover"
          />
          <img
            v-if="verificationCapturedImage"
            :src="verificationCapturedImage"
            alt="Verification photo"
            class="w-full h-full object-cover"
          />
          <div
            v-if="!isVerificationCameraActive && !verificationCapturedImage"
            class="absolute inset-0 flex items-center justify-center"
          >
            <i class="pi pi-camera text-6xl text-gray-400"></i>
          </div>

          <!-- Verification Progress Overlay -->
          <div
            v-if="verifying"
            class="absolute inset-0 bg-black bg-opacity-70 flex items-center justify-center"
          >
            <div class="text-center text-white">
              <i class="pi pi-spin pi-spinner text-4xl mb-3"></i>
              <div class="text-lg">{{ t('Verifying...') }}</div>
            </div>
          </div>
        </div>

        <!-- Instructions -->
        <Message
          v-if="!showVerificationResult"
          severity="info"
          :closable="false"
        >
          {{ t('Position your face in the center and ensure good lighting') }}
        </Message>
      </div>

      <template #footer>
        <div v-if="!verificationCapturedImage" class="flex justify-center gap-2">
          <Button
            :label="t('Cancel')"
            icon="pi pi-times"
            severity="secondary"
            @click="closeVerificationCamera"
          />
          <Button
            :label="t('Capture')"
            icon="pi pi-camera"
            severity="success"
            :disabled="!isVerificationCameraActive"
            @click="captureVerificationPhoto"
          />
        </div>
        <div v-else-if="!showVerificationResult" class="flex justify-center gap-2">
          <Button
            :label="t('Retake')"
            icon="pi pi-replay"
            severity="secondary"
            @click="retakeVerificationPhoto"
          />
          <Button
            :label="t('Verify Face')"
            icon="pi pi-verified"
            severity="success"
            :loading="verifying"
            @click="verifyPhoto"
          />
        </div>
        <div v-else class="flex justify-center gap-2">
          <Button
            :label="t('Close')"
            icon="pi pi-times"
            severity="secondary"
            @click="closeVerificationCamera"
          />
          <Button
            :label="t('Test Again')"
            icon="pi pi-replay"
            severity="info"
            @click="testAgain"
          />
        </div>
      </template>
    </Dialog>
  </AppLayout>
</template>

