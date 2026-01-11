document.addEventListener('DOMContentLoaded', function() {
    // Toast notification handling
    initializeToast();
    
    // Voice recording for phonetic input
    initializeVoiceRecording();
    
    // Pronunciation buttons
    initializePronunciation();
    
    // Copy word buttons
    initializeCopyButtons();
    
    // Handle form submission - auto-scroll when errors
    const form = document.querySelector('.contribution-form');
    if (form) {
        form.addEventListener('submit', function() {
            // This runs before submission - will be helpful if there are errors
            setTimeout(() => {
                const firstError = document.querySelector('.error-message');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        });
    }
    
    // Search form auto-submit on input (with debounce)
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                document.querySelector('.search-form').submit();
            }, 500);
        });
    }
});

// Initialize toast notifications
function initializeToast() {
    const toast = document.getElementById('toast');
    if (toast) {
        const closeButton = document.getElementById('toastClose');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                toast.style.display = 'none';
            });
        }
        
        // Auto-hide toast after 5 seconds
        setTimeout(() => {
            if (toast) {
                toast.style.display = 'none';
            }
        }, 5000);
    }
}

// Voice recording functionality
function initializeVoiceRecording() {
    const recordButton = document.getElementById('recordButton');
    if (!recordButton) return;
    
    const phoneticInput = document.getElementById('phoneticSpelling');
    const recordingIndicator = document.getElementById('recordingIndicator');
    
    let recognition;
    let isRecording = false;
    
    // Enhanced browser compatibility for speech recognition
    if ('webkitSpeechRecognition' in window) {
        recognition = new webkitSpeechRecognition();
    } else if ('SpeechRecognition' in window) {
        recognition = new SpeechRecognition();
    }
    
    if (recognition) {
        // Configure the recognition
        recognition.continuous = false;
        recognition.interimResults = true;
        recognition.lang = 'en-US'; // Default language, can be changed
        
        recognition.onstart = function() {
            isRecording = true;
            recordButton.innerHTML = '<i class="ri-stop-line"></i>';
            recordButton.style.backgroundColor = '#e53e3e';
            recordingIndicator.classList.remove('hidden');
            
            // Log for debugging
            console.log('Voice recording started');
        };
        
        recognition.onresult = function(event) {
            // Log for debugging
            console.log('Got speech result:', event.results);
            
            const transcript = event.results[0][0].transcript;
            phoneticInput.value = transcript;
        };
        
        recognition.onerror = function(event) {
            console.error('Speech recognition error:', event.error);
            
            // Display error message to user
            if (event.error === 'not-allowed') {
                alert('Microphone access denied. Please allow microphone access in your browser settings to use voice recording.');
            } else if (event.error === 'no-speech') {
                alert('No speech was detected. Please try again and speak clearly.');
            }
            
            // Reset UI
            isRecording = false;
            recordButton.innerHTML = '<i class="ri-mic-line"></i>';
            recordButton.style.backgroundColor = '';
            recordingIndicator.classList.add('hidden');
        };
        
        recognition.onend = function() {
            console.log('Voice recording ended');
            
            isRecording = false;
            recordButton.innerHTML = '<i class="ri-mic-line"></i>';
            recordButton.style.backgroundColor = '';
            recordingIndicator.classList.add('hidden');
        };
        
        // Handle button click
        recordButton.addEventListener('click', function() {
            if (isRecording) {
                recognition.stop();
            } else {
                // Reset any previous recordings
                recognition.abort();
                
                // Make sure the browser is properly starting the recording
                try {
                    // Request microphone permission explicitly
                    navigator.mediaDevices.getUserMedia({ audio: true })
                        .then(function(stream) {
                            // Successfully got microphone access, start recognition
                            stream.getTracks().forEach(track => track.stop()); // Stop the stream as we only needed permission
                            recognition.start();
                        })
                        .catch(function(err) {
                            console.error('Microphone access denied:', err);
                            alert('Microphone access is required for voice recording. Please allow access when prompted.');
                        });
                } catch (e) {
                    console.error('Error starting recognition:', e);
                    alert('There was an error starting voice recognition. Please try again or use manual input.');
                }
            }
        });
    } else {
        // Speech recognition not supported
        console.warn('Speech recognition not supported in this browser');
        recordButton.disabled = true;
        recordButton.title = 'Speech recognition not supported in your browser';
        recordButton.style.opacity = '0.5';
        recordButton.style.cursor = 'not-allowed';
        
        // Show fallback notice
        const noticeElem = document.createElement('div');
        noticeElem.className = 'error-message';
        noticeElem.textContent = 'Voice recording is not supported in your browser. Please type the phonetic spelling manually.';
        recordButton.parentNode.parentNode.appendChild(noticeElem);
    }
}

// Pronunciation feature
function initializePronunciation() {
    const pronounceButtons = document.querySelectorAll('.pronounce-btn');
    
    pronounceButtons.forEach(button => {
        button.addEventListener('click', function() {
            const text = this.getAttribute('data-text');
            if (text && 'speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.rate = 0.8; // Slightly slower for better clarity
                window.speechSynthesis.speak(utterance);
                
                // Visual feedback
                this.classList.add('playing');
                setTimeout(() => {
                    this.classList.remove('playing');
                }, 1500);
            }
        });
    });
}

// Copy to clipboard functionality
function initializeCopyButtons() {
    const copyButtons = document.querySelectorAll('.copy-btn');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const text = this.getAttribute('data-text');
            if (text) {
                navigator.clipboard.writeText(text)
                    .then(() => {
                        // Show temporary success message
                        const originalHTML = this.innerHTML;
                        this.innerHTML = '<i class="ri-check-line"></i>';
                        
                        setTimeout(() => {
                            this.innerHTML = originalHTML;
                        }, 1500);
                    })
                    .catch(err => {
                        console.error('Failed to copy text: ', err);
                    });
            }
        });
    });
}

// Search functionality enhancements
function scrollToHash() {
    const hash = window.location.hash;
    if (hash) {
        const targetElement = document.querySelector(hash);
        if (targetElement) {
            setTimeout(() => {
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }
}

// Execute hash navigation after page load
window.addEventListener('load', scrollToHash);