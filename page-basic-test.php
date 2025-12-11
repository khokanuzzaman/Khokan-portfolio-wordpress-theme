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
$redirect_link = $quiz['redirect_link'] ?? '';
$coupon_code = $quiz['coupon_code'] ?? '';
$coupon_param = $quiz['coupon_param'] ?? 'coupon';
$ajax_url = admin_url('admin-ajax.php');
$quiz_nonce = wp_create_nonce('jrc_quiz_submit');
$language_map = [];
foreach ($questions as $question) {
    $label = trim($question['language'] ?? '');
    if ($label === '') {
        continue;
    }
    $key = sanitize_title($label);
    if ($key === '') {
        continue;
    }
    if (!isset($language_map[$key])) {
        $language_map[$key] = $label;
    }
}
$languages = $language_map;
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
                data-pass="<?php echo esc_attr($pass_percent); ?>"
                data-time="<?php echo esc_attr($time_limit); ?>"
                data-redirect="<?php echo esc_url($redirect_link); ?>"
                data-coupon="<?php echo esc_attr($coupon_code); ?>"
                data-coupon-param="<?php echo esc_attr($coupon_param); ?>"
                data-ajax="<?php echo esc_url($ajax_url); ?>"
                data-nonce="<?php echo esc_attr($quiz_nonce); ?>">
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
                    <p class="quiz-user__note">Basic test নেওয়ার আগে নাম ও ফোন দিন। 60%+ হলে coupon auto-apply হবে।</p>
                </div>
                <?php foreach ($questions as $index => $question) : ?>
                    <?php
                    $question_id = 'q' . $index;
                    $question_text = nl2br(esc_html($question['question'] ?? ''));
                    $question_type = $question['type'] ?? 'mcq';
                    $options = $question['options'] ?? [];
                    ?>
                    <?php $language_key = sanitize_title(trim($question['language'] ?? '')); ?>
                    <div class="quiz-question" data-language="<?php echo esc_attr($language_key); ?>" data-type="<?php echo esc_attr($question_type); ?>" data-answer="<?php echo esc_attr($question['answer'] ?? ''); ?>" data-points="<?php echo esc_attr((int) ($question['points'] ?? 1)); ?>">
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
        var passPercent = parseInt(form.getAttribute('data-pass'), 10) || 60;
        var redirectLink = form.getAttribute('data-redirect') || '';
        var couponCode = form.getAttribute('data-coupon') || '';
        var couponParam = form.getAttribute('data-coupon-param') || 'coupon';
        var ajaxUrl = form.getAttribute('data-ajax') || '';
        var nonce = form.getAttribute('data-nonce') || '';
        var nameInput = document.getElementById('quiz-student-name');
        var phoneInput = document.getElementById('quiz-student-phone');
        var emailInput = document.getElementById('quiz-student-email');
        var timerId = null;

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

        function normalizeAnswer(value) {
            return String(value || '').trim().toLowerCase();
        }

        function computeScore() {
            var visible = getVisibleQuestions();
            var totalPoints = 0;
            var score = 0;

            visible.forEach(function (question) {
                var type = question.getAttribute('data-type') || 'mcq';
                var answer = question.getAttribute('data-answer') || '';
                var points = parseInt(question.getAttribute('data-points'), 10) || 1;
                totalPoints += points;

                var userAnswer = '';
                if (type === 'mcq') {
                    var checked = question.querySelector('input[type="radio"]:checked');
                    userAnswer = checked ? checked.value : '';
                } else {
                    var input = question.querySelector('.quiz-input');
                    userAnswer = input ? input.value : '';
                }

                var expected = answer
                    .split('|')
                    .map(function (item) { return normalizeAnswer(item); })
                    .filter(Boolean);
                var userNormalized = normalizeAnswer(userAnswer);

                if (expected.length && expected.indexOf(userNormalized) !== -1) {
                    score += points;
                }
            });

            return {
                score: score,
                total: totalPoints
            };
        }

        function sendAttempt(payload) {
            if (!ajaxUrl || !nonce) {
                return;
            }
            var formData = new FormData();
            Object.keys(payload).forEach(function (key) {
                if (payload[key] === undefined || payload[key] === null) {
                    return;
                }
                formData.append(key, payload[key]);
            });
            if (navigator.sendBeacon) {
                navigator.sendBeacon(ajaxUrl, formData);
                return;
            }
            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            }).catch(function () {});
        }

        function buildRedirectUrl(withCoupon) {
            if (!redirectLink) {
                return '';
            }
            if (!withCoupon || !couponCode) {
                return redirectLink;
            }
            try {
                var url = new URL(redirectLink, window.location.href);
                url.searchParams.set(couponParam || 'coupon', couponCode);
                return url.toString();
            } catch (e) {
                var separator = redirectLink.indexOf('?') === -1 ? '?' : '&';
                return redirectLink + separator + encodeURIComponent(couponParam || 'coupon') + '=' + encodeURIComponent(couponCode);
            }
        }

        function showMessage(message) {
            result.hidden = false;
            result.innerHTML = message;
        }

        function showResult(scoreData) {
            var percent = scoreData.total ? Math.round((scoreData.score / scoreData.total) * 100) : 0;
            var passed = percent >= passPercent;
            var redirectUrl = buildRedirectUrl(passed);
            result.hidden = false;
            var note = '';
            if (passed) {
                note = redirectUrl
                    ? 'Passed! Enrollment form খুলছে...'
                    : 'Passed! Enrollment form link সেট করা হয়নি।';
            } else {
                note = redirectUrl
                    ? 'Coupon পাবেন না, কিন্তু enrollment form খুলছে...'
                    : 'Enroll করতে অন্তত ' + passPercent + '% দরকার।';
            }
            result.innerHTML =
                '<strong>Score:</strong> ' + scoreData.score + '/' + scoreData.total +
                ' (' + percent + '%) - ' + (passed ? 'Passed' : 'Needs Foundation') +
                '<div>' + note + '</div>';
            return {
                passed: passed,
                redirectUrl: redirectUrl
            };
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var missing = [];
            if (nameInput && !nameInput.value.trim()) {
                missing.push('নাম');
            }
            if (phoneInput && !phoneInput.value.trim()) {
                missing.push('ফোন');
            }
            if (missing.length) {
                showMessage('<strong>অনুগ্রহ করে দিন:</strong> ' + missing.join(', '));
                return;
            }
            if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                showMessage('ফর্মের প্রয়োজনীয় তথ্য দিন।');
                return;
            }
            var scoreData = computeScore();
            var outcome = showResult(scoreData);
            var percent = scoreData.total ? Math.round((scoreData.score / scoreData.total) * 100) : 0;
            var language = '';
            if (languageSelect) {
                var selected = languageSelect.options[languageSelect.selectedIndex];
                language = selected ? selected.text : languageSelect.value;
            }
            sendAttempt({
                action: 'jrc_quiz_submit',
                nonce: nonce,
                student_name: nameInput ? nameInput.value : '',
                student_phone: phoneInput ? phoneInput.value : '',
                student_email: emailInput ? emailInput.value : '',
                language: language,
                score: scoreData.score,
                total: scoreData.total,
                percent: percent,
                passed: outcome.passed ? '1' : '0',
                coupon: outcome.passed ? couponCode : '',
                redirect: outcome.redirectUrl
            });
            if (outcome.redirectUrl) {
                setTimeout(function () {
                    window.location.href = outcome.redirectUrl;
                }, 2000);
            }
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                form.reset();
                if (result) {
                    result.hidden = true;
                }
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
