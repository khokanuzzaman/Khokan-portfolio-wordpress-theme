<?php
/**
 * Template Name: Basic Test
 * Description: Basic programming screening test page.
 */

get_header();

$quiz = function_exists('jrc_get_quiz_data') ? jrc_get_quiz_data() : [];
$questions = $quiz['questions'] ?? [];
$time_limit = (int) ($quiz['time_limit'] ?? 0);
$pass_percent = (int) ($quiz['pass_percent'] ?? 60);
$ajax_url = admin_url('admin-ajax.php');
$quiz_nonce = wp_create_nonce('jrc_quiz_submit');
$languages = function_exists('jrc_get_quiz_language_map') ? jrc_get_quiz_language_map($questions) : [];
$default_language = count($languages) === 1 ? array_key_first($languages) : '';
?>
<main class="course-page">
    <section class="section quiz-section">
        <div class="container quiz-card">
            <div class="section-heading">
                <h2><?php echo esc_html($quiz['title'] ?? 'Basic Programming Test'); ?></h2>
                <?php if (!empty($quiz['subtitle'])) : ?>
                    <p class="section-subtitle"><?php echo esc_html($quiz['subtitle']); ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($quiz['discount_note'])) : ?>
                <div class="quiz-discount"><?php echo esc_html($quiz['discount_note']); ?></div>
            <?php endif; ?>
            <div class="quiz-meta">
                <?php if ($time_limit > 0) : ?>
                    <span class="quiz-meta__item">Time: <?php echo esc_html($time_limit); ?> minutes</span>
                    <span class="quiz-meta__item" id="quiz-timer"></span>
                <?php endif; ?>
                <span class="quiz-meta__item">Pass: <?php echo esc_html($pass_percent); ?>%</span>
            </div>
            <?php if (count($languages) > 1) : ?>
                <div class="quiz-language">
                    <label for="quiz-language">Language</label>
                    <select id="quiz-language">
                        <?php foreach ($languages as $language_key => $language_label) : ?>
                            <option value="<?php echo esc_attr($language_key); ?>"><?php echo esc_html($language_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <form id="basic-quiz" class="quiz-form"
                data-time="<?php echo esc_attr($time_limit); ?>"
                data-ajax="<?php echo esc_url($ajax_url); ?>"
                data-nonce="<?php echo esc_attr($quiz_nonce); ?>"
                data-default-language="<?php echo esc_attr($default_language); ?>">
                <div class="quiz-user">
                    <div class="quiz-user__title">Student Info</div>
                    <div class="quiz-user__grid">
                        <label class="quiz-user__field">
                            <span>Full Name *</span>
                            <input class="quiz-input" id="quiz-student-name" name="student_name" type="text" autocomplete="name" required>
                        </label>
                        <label class="quiz-user__field">
                            <span>Phone *</span>
                            <input class="quiz-input" id="quiz-student-phone" name="student_phone" type="tel" autocomplete="tel" required>
                        </label>
                        <label class="quiz-user__field">
                            <span>Email</span>
                            <input class="quiz-input" id="quiz-student-email" name="student_email" type="email" autocomplete="email">
                        </label>
                    </div>
                    <p class="quiz-user__note">Basic test নেওয়ার আগে নাম ও ফোন দিন। Result save হবে এবং next step admin review-এর জন্য ব্যবহার হবে।</p>
                </div>
                <div style="position:absolute;left:-9999px;" aria-hidden="true">
                    <label for="quiz-website">Website</label>
                    <input type="text" id="quiz-website" name="quiz_website" tabindex="-1" autocomplete="off">
                </div>
                <?php foreach ($questions as $index => $question) : ?>
                    <?php
                    $question_id = 'q' . $index;
                    $question_text = nl2br(esc_html($question['question'] ?? ''));
                    $question_type = $question['type'] ?? 'mcq';
                    $options = $question['options'] ?? [];
                    ?>
                    <?php $language_key = sanitize_title(trim($question['language'] ?? '')); ?>
                    <div class="quiz-question" data-language="<?php echo esc_attr($language_key); ?>" data-type="<?php echo esc_attr($question_type); ?>">
                        <div class="quiz-question__title">
                            <span class="quiz-question__label">Q<?php echo esc_html($index + 1); ?></span>
                            <span class="quiz-question__text"><?php echo $question_text; ?></span>
                        </div>
                        <?php if ($question_type === 'mcq') : ?>
                            <div class="quiz-options">
                                <?php foreach ($options as $option_index => $option) : ?>
                                    <label class="quiz-option">
                                        <input type="radio" name="<?php echo esc_attr($question_id); ?>" value="<?php echo esc_attr($option); ?>">
                                        <span><?php echo esc_html($option); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($question_type === 'output') : ?>
                            <textarea class="quiz-input" name="<?php echo esc_attr($question_id); ?>" rows="3" placeholder="Write the output"></textarea>
                        <?php else : ?>
                            <input class="quiz-input" type="text" name="<?php echo esc_attr($question_id); ?>" placeholder="Your answer">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="quiz-actions">
                    <button type="submit" class="primary-btn">Submit Test</button>
                    <button type="button" class="secondary-btn" id="quiz-reset">Reset</button>
                </div>
            </form>
            <div id="quiz-result" class="quiz-result" hidden></div>
        </div>
    </section>
</main>
<script>
    (function () {
        var form = document.getElementById('basic-quiz');
        if (!form) {
            return;
        }
        var questions = Array.prototype.slice.call(form.querySelectorAll('.quiz-question'));
        var languageSelect = document.getElementById('quiz-language');
        var result = document.getElementById('quiz-result');
        var resetBtn = document.getElementById('quiz-reset');
        var timerOutput = document.getElementById('quiz-timer');
        var timeLimit = parseInt(form.getAttribute('data-time'), 10) || 0;
        var ajaxUrl = form.getAttribute('data-ajax') || '';
        var nonce = form.getAttribute('data-nonce') || '';
        var defaultLanguage = form.getAttribute('data-default-language') || '';
        var nameInput = document.getElementById('quiz-student-name');
        var phoneInput = document.getElementById('quiz-student-phone');
        var emailInput = document.getElementById('quiz-student-email');
        var honeypotInput = document.getElementById('quiz-website');
        var submitBtn = form.querySelector('button[type="submit"]');
        var timerId = null;
        var isSubmitting = false;

        function filterByLanguage(lang) {
            var normalized = String(lang || '').trim().toLowerCase();
            questions.forEach(function (question) {
                var qLang = String(question.getAttribute('data-language') || '').trim().toLowerCase();
                var visible = !normalized || qLang === normalized;
                question.hidden = !visible;
                question.style.display = visible ? '' : 'none';
                if (!visible) {
                    var checked = question.querySelector('input[type="radio"]:checked');
                    if (checked) {
                        checked.checked = false;
                    }
                    var input = question.querySelector('.quiz-input');
                    if (input) {
                        input.value = '';
                    }
                }
            });
        }

        function getVisibleQuestions() {
            return questions.filter(function (question) {
                return !question.hidden;
            });
        }

        function clearResult() {
            if (!result) {
                return;
            }

            result.hidden = true;
            result.replaceChildren();
        }

        function showMessage(message) {
            if (!result) {
                return;
            }

            result.hidden = false;
            result.replaceChildren();

            var paragraph = document.createElement('p');
            paragraph.textContent = message;
            result.appendChild(paragraph);
        }

        function showResult(payload) {
            if (!result) {
                return;
            }

            var note = payload.redirect
                ? 'Test submitted. Enrollment form খুলছে...'
                : 'Test submitted. Your result has been saved.';

            result.hidden = false;
            result.replaceChildren();

            var strong = document.createElement('strong');
            strong.textContent = 'Score:';

            var summary = document.createTextNode(
                ' ' + payload.score + '/' + payload.total +
                ' (' + payload.percent + '%) - ' + (payload.passed ? 'Passed' : 'Needs Foundation')
            );
            var noteNode = document.createElement('div');
            noteNode.textContent = note;

            result.appendChild(strong);
            result.appendChild(summary);
            result.appendChild(noteNode);
        }

        function collectAnswers() {
            var answers = {};

            getVisibleQuestions().forEach(function (question) {
                var checked = question.querySelector('input[type="radio"]:checked');
                if (checked) {
                    answers[checked.name] = checked.value;
                    return;
                }

                var input = question.querySelector('.quiz-input');
                if (input && input.name) {
                    answers[input.name] = input.value || '';
                    return;
                }

                var radio = question.querySelector('input[type="radio"]');
                if (radio && radio.name) {
                    answers[radio.name] = '';
                }
            });

            return answers;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (isSubmitting) {
                return;
            }

            var missing = [];
            if (nameInput && !nameInput.value.trim()) {
                missing.push('নাম');
            }
            if (phoneInput && !phoneInput.value.trim()) {
                missing.push('ফোন');
            }
            if (missing.length) {
                showMessage('অনুগ্রহ করে দিন: ' + missing.join(', '));
                return;
            }
            if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                showMessage('ফর্মের প্রয়োজনীয় তথ্য দিন।');
                return;
            }

            var language = languageSelect ? (languageSelect.value || '') : defaultLanguage;
            var answers = collectAnswers();
            var formData = new FormData();

            formData.append('action', 'jrc_quiz_submit');
            formData.append('nonce', nonce);
            formData.append('student_name', nameInput ? nameInput.value : '');
            formData.append('student_phone', phoneInput ? phoneInput.value : '');
            formData.append('student_email', emailInput ? emailInput.value : '');
            formData.append('language', language);
            formData.append('quiz_website', honeypotInput ? honeypotInput.value : '');

            Object.keys(answers).forEach(function (key) {
                formData.append('answers[' + key + ']', answers[key]);
            });

            isSubmitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            showMessage('Submitting your test...');

            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            }).then(function (response) {
                return response.json().catch(function () {
                    return {
                        success: false,
                        data: {
                            message: 'Could not submit the test right now.'
                        }
                    };
                });
            }).then(function (payload) {
                if (!payload || !payload.success || !payload.data) {
                    showMessage(payload && payload.data && payload.data.message ? payload.data.message : 'Could not submit the test right now.');
                    return;
                }

                showResult(payload.data);

                if (payload.data.redirect) {
                    setTimeout(function () {
                        window.location.href = payload.data.redirect;
                    }, 2000);
                }
            }).catch(function () {
                showMessage('Could not submit the test right now.');
            }).finally(function () {
                isSubmitting = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                form.reset();
                clearResult();
            });
        }

        if (languageSelect) {
            if (!languageSelect.value && languageSelect.options.length) {
                languageSelect.value = languageSelect.options[0].value;
            }
            filterByLanguage(languageSelect.value);
            languageSelect.addEventListener('change', function () {
                filterByLanguage(languageSelect.value);
            });
        } else if (defaultLanguage) {
            filterByLanguage(defaultLanguage);
        }

        if (timeLimit > 0 && timerOutput) {
            var remaining = timeLimit * 60;
            timerOutput.textContent = 'Time left: ' + Math.ceil(remaining / 60) + 'm';
            timerId = setInterval(function () {
                remaining -= 1;
                if (remaining < 0) {
                    clearInterval(timerId);
                    form.dispatchEvent(new Event('submit', { cancelable: true }));
                    return;
                }
                var mins = Math.floor(remaining / 60);
                var secs = remaining % 60;
                timerOutput.textContent = 'Time left: ' + mins + 'm ' + secs + 's';
            }, 1000);
        }
    })();
</script>
<?php get_footer(); ?>
