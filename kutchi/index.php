<?php
// Initialize dictionary data or load from storage
session_start();

// File-based storage for better persistence across devices
$dictionaryFile = 'data/dictionary.json';

// Make sure data directory exists
if (!file_exists('data')) {
    mkdir('data', 0777, true);
}

// Load existing data or initialize with defaults
if (file_exists($dictionaryFile)) {
    $dictionaryData = json_decode(file_get_contents($dictionaryFile), true);
    if (!is_array($dictionaryData)) {
        // Fallback if file is corrupted
        $dictionaryData = [
            [
                'id' => 1,
                'kutchiWord' => 'Pani',
                'phoneticSpelling' => 'Paw-nee',
                'englishTranslation' => 'Water'
            ],
            [
                'id' => 2,
                'kutchiWord' => 'Ki Ayo',
                'phoneticSpelling' => 'Kee Ey-yo',
                'englishTranslation' => 'What happened'
            ]
        ];
    }
} else {
    // Default initial data
    $dictionaryData = [
        [
            'id' => 1,
            'kutchiWord' => 'Pani',
            'phoneticSpelling' => 'Paw-nee',
            'englishTranslation' => 'Water'
        ],
        [
            'id' => 2,
            'kutchiWord' => 'Ki Ayo',
            'phoneticSpelling' => 'Kee Ey-yo',
            'englishTranslation' => 'What happened'
        ]
    ];
    
    // Save default data to file
    file_put_contents($dictionaryFile, json_encode($dictionaryData, JSON_PRETTY_PRINT));
}

// Store data in session for easier access during this request
$_SESSION['dictionary'] = $dictionaryData;

// Handle actions for adding, editing, and deleting words
$notification = '';
$isSuccess = false;

// Edit mode variables
$editMode = false;
$editWordId = null;
$editKutchiWord = '';
$editPhoneticSpelling = '';
$editEnglishTranslation = '';

// Load word for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editWordId = (int)$_GET['edit'];
    
    // Find the word to edit
    foreach ($dictionaryData as $word) {
        if ($word['id'] == $editWordId) {
            $editMode = true;
            $editKutchiWord = $word['kutchiWord'];
            $editPhoneticSpelling = $word['phoneticSpelling'];
            $editEnglishTranslation = $word['englishTranslation'];
            break;
        }
    }
}

// Delete word action
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // Filter out the word to be deleted
    $updatedDictionary = array_filter($dictionaryData, function($word) use ($deleteId) {
        return $word['id'] != $deleteId;
    });
    
    // Re-index array to be sequential
    $dictionaryData = array_values($updatedDictionary);
    $_SESSION['dictionary'] = $dictionaryData;
    
    // Save updated data to file
    file_put_contents($dictionaryFile, json_encode($dictionaryData, JSON_PRETTY_PRINT));
    
    // Success notification
    $notification = 'Word successfully deleted from the dictionary!';
    $isSuccess = true;
}

// Handle form submission for adding or updating words
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'add_word' || $_POST['action'] === 'update_word')) {
    $kutchiWord = trim($_POST['kutchiWord']);
    $phoneticSpelling = trim($_POST['phoneticSpelling']);
    $englishTranslation = trim($_POST['englishTranslation']);
    $isUpdate = $_POST['action'] === 'update_word';
    $wordId = $isUpdate ? (int)$_POST['wordId'] : 0;
    
    // Validation
    $errors = [];
    if (empty($kutchiWord)) {
        $errors['kutchiWord'] = 'Kutchi word is required';
    }
    if (empty($phoneticSpelling)) {
        $errors['phoneticSpelling'] = 'Phonetic spelling is required';
    }
    if (empty($englishTranslation)) {
        $errors['englishTranslation'] = 'English translation is required';
    }
    
    // If no errors, proceed with add or update
    if (empty($errors)) {
        if ($isUpdate) {
            // Update existing word
            $wordUpdated = false;
            
            foreach ($dictionaryData as $key => $word) {
                if ($word['id'] == $wordId) {
                    $dictionaryData[$key]['kutchiWord'] = $kutchiWord;
                    $dictionaryData[$key]['phoneticSpelling'] = $phoneticSpelling;
                    $dictionaryData[$key]['englishTranslation'] = $englishTranslation;
                    $wordUpdated = true;
                    break;
                }
            }
            
            if ($wordUpdated) {
                $_SESSION['dictionary'] = $dictionaryData;
                
                // Save updated data to file
                file_put_contents($dictionaryFile, json_encode($dictionaryData, JSON_PRETTY_PRINT));
                
                // Success notification
                $notification = 'Word successfully updated!';
                $isSuccess = true;
                
                // Reset edit mode
                $editMode = false;
            }
        } else {
            // Adding new word - check for duplicates
            $duplicate = false;
            $existingWord = null;
            
            foreach ($dictionaryData as $word) {
                if (strtolower($word['kutchiWord']) === strtolower($kutchiWord)) {
                    $duplicate = true;
                    $existingWord = $word;
                    break;
                }
            }
            
            $forceAdd = isset($_POST['forceAdd']) && $_POST['forceAdd'] === 'true';
            
            if ($duplicate && !$forceAdd) {
                // Set duplicate flag for modal display
                $showDuplicateModal = true;
                $duplicateWord = $kutchiWord;
                $duplicateTranslation = $existingWord['englishTranslation'];
            } else {
                // Find the highest ID
                $maxId = 0;
                foreach ($dictionaryData as $word) {
                    if ($word['id'] > $maxId) {
                        $maxId = $word['id'];
                    }
                }
                
                // Add the new word
                $newWord = [
                    'id' => $maxId + 1,
                    'kutchiWord' => $kutchiWord,
                    'phoneticSpelling' => $phoneticSpelling,
                    'englishTranslation' => $englishTranslation
                ];
                
                $dictionaryData[] = $newWord;
                $_SESSION['dictionary'] = $dictionaryData;
                
                // Save updated data to file
                file_put_contents($dictionaryFile, json_encode($dictionaryData, JSON_PRETTY_PRINT));
                
                // Success notification
                $notification = 'Word successfully added to the dictionary!';
                $isSuccess = true;
                
                // Reset form data
                $kutchiWord = '';
                $phoneticSpelling = '';
                $englishTranslation = '';
            }
        }
    } else if ($isUpdate) {
        // Maintain edit mode if there are errors
        $editMode = true;
        $editWordId = $wordId;
        $editKutchiWord = $kutchiWord;
        $editPhoneticSpelling = $phoneticSpelling;
        $editEnglishTranslation = $englishTranslation;
    }
}

// Handle duplicate confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_duplicate') {
    $kutchiWord = trim($_POST['kutchiWord']);
    $phoneticSpelling = trim($_POST['phoneticSpelling']);
    $englishTranslation = trim($_POST['englishTranslation']);
    
    // Find the highest ID
    $maxId = 0;
    foreach ($dictionaryData as $word) {
        if ($word['id'] > $maxId) {
            $maxId = $word['id'];
        }
    }
    
    // Add the new word
    $newWord = [
        'id' => $maxId + 1,
        'kutchiWord' => $kutchiWord,
        'phoneticSpelling' => $phoneticSpelling,
        'englishTranslation' => $englishTranslation
    ];
    
    $dictionaryData[] = $newWord;
    $_SESSION['dictionary'] = $dictionaryData;
    
    // Save updated data to file
    file_put_contents($dictionaryFile, json_encode($dictionaryData, JSON_PRETTY_PRINT));
    
    // Success notification
    $notification = 'Word successfully added to the dictionary!';
    $isSuccess = true;
    
    // Reset form data
    $kutchiWord = '';
    $phoneticSpelling = '';
    $englishTranslation = '';
}

// Group words by first letter
function getFirstLetter($word) {
    return strtoupper(substr($word, 0, 1));
}

function groupWordsByFirstLetter($words) {
    $grouped = [];
    $alphabet = range('A', 'Z');
    
    // Initialize all letters
    foreach ($alphabet as $letter) {
        $grouped[$letter] = [];
    }
    
    // Group words by first letter
    foreach ($words as $word) {
        $firstLetter = getFirstLetter($word['kutchiWord']);
        if (ctype_alpha($firstLetter)) { // Ensure it's a letter
            $grouped[$firstLetter][] = $word;
        }
    }
    
    return $grouped;
}

// Get words grouped by first letter
$wordsByLetter = groupWordsByFirstLetter($_SESSION['dictionary']);

// Search functionality
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filteredDictionary = $_SESSION['dictionary'];

if (!empty($searchTerm)) {
    $filteredDictionary = array_filter($_SESSION['dictionary'], function($word) use ($searchTerm) {
        return (stripos($word['kutchiWord'], $searchTerm) !== false) || 
               (stripos($word['englishTranslation'], $searchTerm) !== false);
    });
    
    $wordsByLetter = groupWordsByFirstLetter($filteredDictionary);
}

// Sort words in each letter group alphabetically
foreach ($wordsByLetter as $letter => $words) {
    usort($wordsByLetter[$letter], function($a, $b) {
        return strcasecmp($a['kutchiWord'], $b['kutchiWord']);
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kutchi Dictionary</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&family=Amita:wght@400;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <div class="logo-wrapper">
                        <div class="logo-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M2 17L12 22L22 17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M2 12L12 17L22 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <h1 class="site-title">Kutchi Dictionary</h1>
                    </div>
                    
                    <p class="site-subtitle">Preserving an ancient unwritten language</p>
                </div>
                
                <div class="header-description">
                    <p>A collaborative platform to document and preserve the Kutchi language through community contributions. Add words, explore translations, and help keep this cultural heritage alive.</p>
                </div>
            </div>
        </header>
        
        <main class="container flex-grow py-8">
            <div class="content-layout">
                <!-- Dictionary Section -->
                <section class="dictionary-section">
                    <div class="card card-decoration">
                        <div class="card-header">
                            <div class="card-header-content">
                                <div class="section-icon">
                                    <i class="ri-book-open-line"></i>
                                </div>
                                <h2 class="section-title">Dictionary</h2>
                                <div class="word-counter">
                                    <span><?php echo count($_SESSION['dictionary']); ?> words</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-content">
                            <!-- Search Box -->
                            <form action="index.php" method="get" class="search-form">
                                <div class="search-box">
                                    <input 
                                        type="text" 
                                        name="search" 
                                        id="search" 
                                        placeholder="Search the dictionary..." 
                                        class="search-input"
                                        value="<?php echo htmlspecialchars($searchTerm); ?>"
                                    >
                                    <i class="ri-search-line search-icon"></i>
                                    <?php if (!empty($searchTerm)): ?>
                                        <a href="index.php" class="clear-search" title="Clear search">×</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                            
                            <!-- Alphabet Navigation -->
                            <div class="alphabet-navigation" id="alphabet-nav">
                                <div class="alphabet-buttons">
                                    <?php foreach (range('A', 'Z') as $letter): ?>
                                        <?php $hasWords = !empty($wordsByLetter[$letter]); ?>
                                        <a 
                                            href="<?php echo $hasWords ? '#section-' . $letter : 'javascript:void(0)'; ?>"
                                            class="alphabet-btn <?php echo $hasWords ? '' : 'alphabet-btn-disabled'; ?>"
                                            <?php if (!$hasWords): ?>aria-disabled="true"<?php endif; ?>
                                        >
                                            <?php echo $letter; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Dictionary Content -->
                            <div class="dictionary-content" id="dictionary-content">
                                <?php if (empty($_SESSION['dictionary'])): ?>
                                    <!-- Empty Dictionary -->
                                    <div class="empty-dictionary">
                                        <div class="empty-icon-wrapper">
                                            <i class="ri-quill-pen-line empty-icon"></i>
                                        </div>
                                        <h3>Dictionary is empty</h3>
                                        <p>Be the first to contribute words to our Kutchi dictionary!</p>
                                        <a href="#contribution-form" class="add-word-btn">
                                            <i class="ri-add-line"></i>
                                            Add Your First Word
                                        </a>
                                    </div>
                                <?php elseif (empty($filteredDictionary)): ?>
                                    <!-- No search results -->
                                    <div class="empty-search">
                                        <p>No words found matching "<?php echo htmlspecialchars($searchTerm); ?>"</p>
                                        <a href="index.php" class="back-btn">
                                            <i class="ri-arrow-left-line"></i>
                                            Back to all words
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <!-- Word Sections -->
                                    <?php foreach ($wordsByLetter as $letter => $words): ?>
                                        <?php if (!empty($words)): ?>
                                            <div class="word-section" id="section-<?php echo $letter; ?>">
                                                <div class="letter-heading">
                                                    <h3 class="letter-title">
                                                        <span class="letter-bg"><?php echo $letter; ?></span>
                                                        <?php echo $letter; ?>
                                                    </h3>
                                                </div>
                                                
                                                <?php foreach ($words as $word): ?>
                                                    <div class="word-entry">
                                                        <div class="word-entry-content">
                                                            <div class="word-details">
                                                                <h4 class="word-kutchi"><?php echo htmlspecialchars($word['kutchiWord']); ?></h4>
                                                                <p class="word-phonetic">/<?php echo htmlspecialchars($word['phoneticSpelling']); ?>/</p>
                                                                <p class="word-translation"><?php echo htmlspecialchars($word['englishTranslation']); ?></p>
                                                            </div>
                                                            <div class="word-actions">
                                                                <button 
                                                                    class="word-action-btn pronounce-btn" 
                                                                    data-text="<?php echo htmlspecialchars($word['phoneticSpelling']); ?>"
                                                                    title="Listen to pronunciation"
                                                                >
                                                                    <i class="ri-volume-up-line"></i>
                                                                </button>
                                                                <button 
                                                                    class="word-action-btn copy-btn"
                                                                    data-text="<?php echo htmlspecialchars($word['kutchiWord']); ?>"
                                                                    title="Copy word"
                                                                >
                                                                    <i class="ri-file-copy-line"></i>
                                                                </button>
                                                                <a 
                                                                    href="index.php?edit=<?php echo $word['id']; ?>" 
                                                                    class="word-action-btn edit-btn"
                                                                    title="Edit word"
                                                                >
                                                                    <i class="ri-edit-line"></i>
                                                                </a>
                                                                <a 
                                                                    href="index.php?delete=<?php echo $word['id']; ?>" 
                                                                    class="word-action-btn delete-btn"
                                                                    title="Delete word"
                                                                    onclick="return confirm('Are you sure you want to delete this word?');"
                                                                >
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Right Column -->
                <div class="right-column">
                    <!-- Contribution Form -->
                    <section class="contribution-section" id="contribution-form">
                        <div class="card card-decoration">
                            <div class="card-header">
                                <div class="card-header-content">
                                    <div class="section-icon">
                                        <i class="ri-add-circle-line"></i>
                                    </div>
                                    <h2 class="section-title">Contribute</h2>
                                </div>
                            </div>
                            
                            <div class="card-content">
                                <?php if ($editMode): ?>
                                    <h3 class="add-word-title">Edit Word</h3>
                                    <p class="add-word-desc">Update the word in the Kutchi dictionary.</p>
                                <?php else: ?>
                                    <h3 class="add-word-title">Add a New Word</h3>
                                    <p class="add-word-desc">Share your knowledge to help preserve the Kutchi language for future generations.</p>
                                    
                                    <div class="quote-box">
                                        Every word you add helps build a bridge between the past and future of this beautiful language.
                                    </div>
                                <?php endif; ?>
                                
                                <form method="post" action="index.php<?php echo $editMode ? '' : '#contribution-form'; ?>" class="contribution-form">
                                    <input type="hidden" name="action" value="<?php echo $editMode ? 'update_word' : 'add_word'; ?>">
                                    <?php if ($editMode): ?>
                                        <input type="hidden" name="wordId" value="<?php echo $editWordId; ?>">
                                    <?php endif; ?>
                                    
                                    <?php if (isset($showDuplicateModal) && $showDuplicateModal): ?>
                                        <input type="hidden" name="forceAdd" value="false">
                                    <?php endif; ?>
                                    
                                    <div class="form-group">
                                        <label for="kutchiWord" class="form-label">
                                            <span class="step">1.</span> Kutchi Word
                                        </label>
                                        <input 
                                            type="text" 
                                            id="kutchiWord" 
                                            name="kutchiWord" 
                                            class="form-input <?php echo isset($errors['kutchiWord']) ? 'error' : ''; ?>" 
                                            placeholder="Enter the word in Kutchi"
                                            value="<?php echo $editMode ? htmlspecialchars($editKutchiWord) : (isset($kutchiWord) ? htmlspecialchars($kutchiWord) : ''); ?>"
                                            required
                                        >
                                        <?php if (isset($errors['kutchiWord'])): ?>
                                            <div class="error-message"><?php echo $errors['kutchiWord']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="phoneticSpelling" class="form-label">
                                            <span class="step">2.</span> Phonetic Spelling
                                        </label>
                                        <div class="input-with-icon">
                                            <input 
                                                type="text" 
                                                id="phoneticSpelling" 
                                                name="phoneticSpelling" 
                                                class="form-input <?php echo isset($errors['phoneticSpelling']) ? 'error' : ''; ?>" 
                                                placeholder="How to pronounce it (e.g., kuh-chee)"
                                                value="<?php echo $editMode ? htmlspecialchars($editPhoneticSpelling) : (isset($phoneticSpelling) ? htmlspecialchars($phoneticSpelling) : ''); ?>"
                                                required
                                            >
                                            <button 
                                                type="button" 
                                                id="recordButton"
                                                class="voice-record-btn"
                                                title="Record pronunciation"
                                            >
                                                <i class="ri-mic-line"></i>
                                            </button>
                                        </div>
                                        <div id="recordingIndicator" class="recording-indicator hidden">
                                            <div class="pulse-dot"></div>
                                            <span>Recording...</span>
                                        </div>
                                        <?php if (isset($errors['phoneticSpelling'])): ?>
                                            <div class="error-message"><?php echo $errors['phoneticSpelling']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="englishTranslation" class="form-label">
                                            <span class="step">3.</span> English Translation
                                        </label>
                                        <input 
                                            type="text" 
                                            id="englishTranslation" 
                                            name="englishTranslation" 
                                            class="form-input <?php echo isset($errors['englishTranslation']) ? 'error' : ''; ?>" 
                                            placeholder="Enter the English meaning"
                                            value="<?php echo $editMode ? htmlspecialchars($editEnglishTranslation) : (isset($englishTranslation) ? htmlspecialchars($englishTranslation) : ''); ?>"
                                            required
                                        >
                                        <?php if (isset($errors['englishTranslation'])): ?>
                                            <div class="error-message"><?php echo $errors['englishTranslation']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <?php if ($editMode): ?>
                                            <a href="index.php" class="cancel-btn">
                                                <i class="ri-close-line"></i>
                                                Cancel
                                            </a>
                                            <button type="submit" class="submit-btn update-btn">
                                                <i class="ri-save-line"></i>
                                                Update Word
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="submit-btn">
                                                <i class="ri-quill-pen-line"></i>
                                                Add to Dictionary
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>

                    <!-- About Kutchi Section -->
                    <section class="about-section">
                        <div class="about-section-header">
                            <div class="about-icon">
                                <i class="ri-information-line"></i>
                            </div>
                            <h3 class="about-title">About Kutchi Language</h3>
                        </div>
                        
                        <div class="about-content">
                            <p class="about-text">
                                Kutchi is an ancient Indian language primarily spoken in the Kutch region of Gujarat. 
                                It has no standardized written form, making this digital preservation effort crucial.
                            </p>
                            
                            <div class="cultural-heritage">
                                <div class="cultural-icon">
                                    <i class="ri-ancient-pavilion-line"></i>
                                </div>
                                <div class="cultural-text">
                                    <strong>Cultural Heritage:</strong> By contributing words to this dictionary, you're helping to preserve an important cultural heritage for future generations of Kutchi speakers.
                                </div>
                            </div>
                            
                            <a href="family.php" class="learn-more-link">
                                Learn more about Kutchi
                                <i class="ri-arrow-right-line"></i>
                            </a>
                        </div>
                    </section>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-branding">
                        <div class="footer-logo">
                            <div class="footer-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <h2 class="footer-title">Kutchi Dictionary</h2>
                        </div>
                        <p class="footer-tagline">Preserving cultural heritage, one word at a time</p>
                    </div>
                    
                    <div class="footer-social">
                        <a href="https://www.instagram.com/anyaganger/" target="_blank" rel="noopener noreferrer" class="social-link">
                            <i class="ri-instagram-line"></i>
                        </a>
                        <a href="mailto:anya.ganger@icloud.com" class="social-link">
                            <i class="ri-mail-line"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p class="copyright">© <?php echo date('Y'); ?> Kutchi Dictionary Project. All rights reserved.</p>
                </div>
            </div>
        </footer>
        
        <!-- Toast Notification -->
        <?php if (!empty($notification)): ?>
        <div id="toast" class="toast <?php echo $isSuccess ? 'success' : 'error'; ?>">
            <div class="toast-content">
                <i class="<?php echo $isSuccess ? 'ri-check-line' : 'ri-error-warning-line'; ?> toast-icon"></i>
                <div class="toast-message"><?php echo $notification; ?></div>
            </div>
            <button id="toastClose" class="toast-close">&times;</button>
        </div>
        <?php endif; ?>
        
        <!-- Duplicate Word Modal -->
        <?php if (isset($showDuplicateModal) && $showDuplicateModal): ?>
        <div id="duplicateModal" class="modal">
            <div class="modal-overlay"></div>
            <div class="modal-container">
                <div class="modal-header">
                    <div class="modal-icon">
                        <i class="ri-alert-line"></i>
                    </div>
                    <h3 class="modal-title">Duplicate Word Detected</h3>
                </div>
                
                <div class="modal-content">
                    <p class="modal-text">
                        "<?php echo htmlspecialchars($duplicateWord); ?>" already exists in the dictionary with the translation "<?php echo htmlspecialchars($duplicateTranslation); ?>". Would you like to add it anyway?
                    </p>
                    
                    <div class="modal-actions">
                        <a href="index.php" class="modal-cancel-btn">
                            Cancel
                        </a>
                        <form method="post" action="index.php" style="display: inline;">
                            <input type="hidden" name="action" value="confirm_duplicate">
                            <input type="hidden" name="kutchiWord" value="<?php echo htmlspecialchars($kutchiWord); ?>">
                            <input type="hidden" name="phoneticSpelling" value="<?php echo htmlspecialchars($phoneticSpelling); ?>">
                            <input type="hidden" name="englishTranslation" value="<?php echo htmlspecialchars($englishTranslation); ?>">
                            <button type="submit" class="modal-submit-btn">
                                Add Anyway
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <script src="js/script.js"></script>
    </div>
</body>
</html>