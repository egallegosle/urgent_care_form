/**
 * Document Upload JavaScript
 * Handles file uploads with drag-and-drop, validation, and preview
 */

class DocumentUploader {
    constructor(uploadAreaId, documentType) {
        this.uploadArea = document.getElementById(uploadAreaId);
        this.documentType = documentType;
        this.fileInput = this.uploadArea.querySelector('.file-input');
        this.uploadBtn = this.uploadArea.querySelector('.upload-btn');
        this.statusDiv = this.uploadArea.querySelector('.upload-status');
        this.progressDiv = this.uploadArea.querySelector('.upload-progress');
        this.progressBar = this.uploadArea.querySelector('.progress-bar');
        this.previewDiv = this.uploadArea.querySelector('.file-preview');
        this.uploadedDocumentId = null;

        this.init();
    }

    init() {
        // Click to upload
        this.uploadArea.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-btn')) {
                return; // Don't trigger file select when clicking remove
            }
            this.fileInput.click();
        });

        // File selected
        this.fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.handleFile(e.target.files[0]);
            }
        });

        // Drag and drop
        this.uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.uploadArea.classList.add('dragover');
        });

        this.uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.uploadArea.classList.remove('dragover');
        });

        this.uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.uploadArea.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.handleFile(files[0]);
            }
        });
    }

    handleFile(file) {
        // Validate file
        const validation = this.validateFile(file);
        if (!validation.valid) {
            this.showStatus('error', validation.error);
            return;
        }

        // Show preview
        this.showPreview(file);

        // Upload file
        this.uploadFile(file);
    }

    validateFile(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

        // Check file size
        if (file.size > maxSize) {
            return {
                valid: false,
                error: 'File size exceeds 5MB maximum'
            };
        }

        // Check file type
        if (!allowedTypes.includes(file.type)) {
            return {
                valid: false,
                error: 'Invalid file type. Only JPG, PNG, and PDF files are allowed'
            };
        }

        // Check extension
        const extension = file.name.split('.').pop().toLowerCase();
        if (!allowedExtensions.includes(extension)) {
            return {
                valid: false,
                error: 'Invalid file extension'
            };
        }

        return { valid: true };
    }

    showPreview(file) {
        if (!this.previewDiv) return;

        // Show file info
        const fileName = this.truncateFileName(file.name, 30);
        const fileSize = this.formatFileSize(file.size);

        let previewHTML = `
            <div class="file-info">
                <span class="file-name" title="${file.name}">${fileName}</span>
                <span class="file-size">${fileSize}</span>
            </div>
        `;

        // Show image preview if it's an image
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewHTML = `
                    <img src="${e.target.result}" alt="Preview" class="preview-image">
                    <div class="file-info">
                        <span class="file-name" title="${file.name}">${fileName}</span>
                        <span class="file-size">${fileSize}</span>
                    </div>
                `;
                this.previewDiv.innerHTML = previewHTML;
                this.previewDiv.classList.add('show');
            };
            reader.readAsDataURL(file);
        } else {
            this.previewDiv.innerHTML = previewHTML;
            this.previewDiv.classList.add('show');
        }
    }

    async uploadFile(file) {
        const formData = new FormData();
        formData.append('document', file);
        formData.append('document_type', this.documentType);

        // Show uploading status
        this.showStatus('uploading', 'Uploading...');
        this.showProgress(0);
        this.uploadBtn.disabled = true;

        try {
            const xhr = new XMLHttpRequest();

            // Progress tracking
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    this.showProgress(percentComplete);
                }
            });

            // Upload complete
            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        this.uploadedDocumentId = response.document_id;
                        this.showStatus('success', '✓ Uploaded successfully');
                        this.hideProgress();
                        this.markAsUploaded();
                        this.uploadBtn.disabled = true;

                        // Store document ID in hidden input for form submission
                        this.storeDocumentId(response.document_id);
                    } else {
                        this.showStatus('error', response.error || 'Upload failed');
                        this.hideProgress();
                        this.uploadBtn.disabled = false;
                    }
                } else {
                    this.showStatus('error', 'Server error occurred');
                    this.hideProgress();
                    this.uploadBtn.disabled = false;
                }
            });

            // Upload error
            xhr.addEventListener('error', () => {
                this.showStatus('error', 'Network error occurred');
                this.hideProgress();
                this.uploadBtn.disabled = false;
            });

            xhr.open('POST', '../process/upload_document.php');
            xhr.send(formData);

        } catch (error) {
            this.showStatus('error', 'Upload failed: ' + error.message);
            this.hideProgress();
            this.uploadBtn.disabled = false;
        }
    }

    showStatus(type, message) {
        if (!this.statusDiv) return;

        this.statusDiv.className = 'upload-status show ' + type;
        this.statusDiv.textContent = message;
    }

    showProgress(percent) {
        if (!this.progressDiv || !this.progressBar) return;

        this.progressDiv.classList.add('show');
        this.progressBar.style.width = percent + '%';
    }

    hideProgress() {
        if (!this.progressDiv) return;

        setTimeout(() => {
            this.progressDiv.classList.remove('show');
            if (this.progressBar) {
                this.progressBar.style.width = '0%';
            }
        }, 500);
    }

    markAsUploaded() {
        this.uploadArea.classList.add('uploaded');
        this.uploadArea.classList.remove('error');
    }

    storeDocumentId(documentId) {
        // Create or update hidden input to track uploaded document
        let hiddenInput = document.getElementById(`document_id_${this.documentType}`);
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.id = `document_id_${this.documentType}`;
            hiddenInput.name = `document_id_${this.documentType}`;
            this.uploadArea.appendChild(hiddenInput);
        }
        hiddenInput.value = documentId;
    }

    formatFileSize(bytes) {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    }

    truncateFileName(name, maxLength) {
        if (name.length <= maxLength) return name;

        const extension = name.split('.').pop();
        const nameWithoutExt = name.substring(0, name.lastIndexOf('.'));
        const truncated = nameWithoutExt.substring(0, maxLength - extension.length - 4) + '...';

        return truncated + '.' + extension;
    }
}

// Initialize uploaders when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Insurance Card uploaders
    if (document.getElementById('insurance-card-front-upload')) {
        new DocumentUploader('insurance-card-front-upload', 'insurance_card_front');
    }

    if (document.getElementById('insurance-card-back-upload')) {
        new DocumentUploader('insurance-card-back-upload', 'insurance_card_back');
    }

    // Photo ID uploaders
    if (document.getElementById('photo-id-front-upload')) {
        new DocumentUploader('photo-id-front-upload', 'photo_id_front');
    }

    if (document.getElementById('photo-id-back-upload')) {
        new DocumentUploader('photo-id-back-upload', 'photo_id_back');
    }
});
