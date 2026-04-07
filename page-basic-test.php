<?php
/**
 * Template Name: Basic Test
 * Description: Basic programming screening test page.
 */

get_header();

$apply_links = [
    'react' => function_exists('jrc_get_course_application_page_url')
        ? jrc_get_course_application_page_url('react')
        : home_url('/'),
    'flutter' => function_exists('jrc_get_course_application_page_url')
        ? jrc_get_course_application_page_url('flutter')
        : home_url('/'),
];

$career_questions = [
    [
        'id' => 'q1',
        'question' => 'তুমি কোন device বেশি ব্যবহার করো?',
        'options' => [
            ['value' => 'mobile', 'label' => 'Mobile phone', 'react' => 0, 'flutter' => 2],
            ['value' => 'laptop', 'label' => 'Laptop/Computer', 'react' => 2, 'flutter' => 0],
            ['value' => 'both', 'label' => 'Both equally', 'react' => 1, 'flutter' => 1],
        ],
    ],
    [
        'id' => 'q2',
        'question' => 'কোন ধরনের project তোমাকে বেশি excite করে?',
        'options' => [
            ['value' => 'mobile_apps', 'label' => 'Mobile apps যেমন Foodpanda, Pathao', 'react' => 0, 'flutter' => 2],
            ['value' => 'websites', 'label' => 'Websites যেমন Daraz, Facebook', 'react' => 2, 'flutter' => 0],
        ],
    ],
    [
        'id' => 'q3',
        'question' => 'তোমার primary career goal কী?',
        'options' => [
            ['value' => 'freelancing', 'label' => 'International freelancing', 'react' => 2, 'flutter' => 0],
            ['value' => 'startup_job', 'label' => 'Local startup এ job', 'react' => 0, 'flutter' => 2],
            ['value' => 'both', 'label' => 'Both', 'react' => 1, 'flutter' => 1],
        ],
    ],
    [
        'id' => 'q4',
        'question' => 'Visual design তোমার কাছে কতটা important?',
        'options' => [
            ['value' => 'very_important', 'label' => 'খুব important', 'react' => 0, 'flutter' => 2],
            ['value' => 'functionality_first', 'label' => 'Functionality আরও important', 'react' => 2, 'flutter' => 0],
        ],
    ],
    [
        'id' => 'q5',
        'question' => 'তোমার laptop spec?',
        'options' => [
            ['value' => '4gb', 'label' => '4GB RAM', 'react' => 1, 'flutter' => 0],
            ['value' => '8gb_plus', 'label' => '8GB+ RAM', 'react' => 1, 'flutter' => 1],
        ],
    ],
    [
        'id' => 'q6',
        'question' => 'তুমি কোনটা আগে শিখেছ?',
        'options' => [
            ['value' => 'html_css_js', 'label' => 'HTML/CSS/JavaScript', 'react' => 2, 'flutter' => 0],
            ['value' => 'java_cpp_python', 'label' => 'Java/C++/Python', 'react' => 0, 'flutter' => 2],
            ['value' => 'nothing_yet', 'label' => 'কিছুই না', 'react' => 1, 'flutter' => 1],
        ],
    ],
    [
        'id' => 'q7',
        'question' => 'তুমি Bangladesh এ থাকবে নাকি foreign এ যাবে?',
        'options' => [
            ['value' => 'bangladesh', 'label' => 'Bangladesh', 'react' => 0, 'flutter' => 2],
            ['value' => 'foreign_remote', 'label' => 'Foreign/Remote', 'react' => 2, 'flutter' => 0],
        ],
    ],
];
?>
<main class="course-page">
    <section class="section quiz-section">
        <div class="container quiz-card">
            <div class="section-heading">
                <h2>Career Path Quiz</h2>
                <p class="section-subtitle">React নাকি Flutter? এই 7টা quick question answer করো, তারপর তোমার best-fit track আর discount code দেখে নাও.</p>
            </div>

            <div class="quiz-discount">Finish the quiz and unlock coupon code <strong>QUIZ1000</strong> for BDT 1,000 off.</div>

            <div class="quiz-meta">
                <span class="quiz-meta__item" id="career-quiz-progress">Answered: 0/<?php echo esc_html(count($career_questions)); ?></span>
                <span class="quiz-meta__item" id="career-quiz-status">2-3 minute-এ শেষ হবে.</span>
                <span class="quiz-meta__item">Result is shareable by URL</span>
            </div>

            <noscript>
                <p class="section-subtitle">এই quiz চালাতে JavaScript চালু থাকতে হবে।</p>
            </noscript>

            <form
                id="career-quiz"
                class="quiz-form"
                data-storage-key="khokan-career-path-quiz-v1"
                data-apply-react="<?php echo esc_url($apply_links['react']); ?>"
                data-apply-flutter="<?php echo esc_url($apply_links['flutter']); ?>">
                <?php foreach ($career_questions as $index => $question) : ?>
                    <div class="quiz-question" data-question="<?php echo esc_attr($question['id']); ?>">
                        <div class="quiz-question__title">
                            <span class="quiz-question__label">Q<?php echo esc_html($index + 1); ?></span>
                            <span class="quiz-question__text"><?php echo esc_html($question['question']); ?></span>
                        </div>

                        <div class="quiz-options">
                            <?php foreach ($question['options'] as $option) : ?>
                                <label class="quiz-option">
                                    <input
                                        type="radio"
                                        name="<?php echo esc_attr($question['id']); ?>"
                                        value="<?php echo esc_attr($option['value']); ?>"
                                        data-react="<?php echo esc_attr((string) $option['react']); ?>"
                                        data-flutter="<?php echo esc_attr((string) $option['flutter']); ?>">
                                    <span><?php echo esc_html($option['label']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="quiz-actions">
                    <button type="submit" class="primary-btn">See My Best Track</button>
                    <button type="button" class="secondary-btn" id="career-quiz-reset">Reset</button>
                </div>
            </form>

            <div id="career-quiz-result" class="quiz-result" hidden></div>
        </div>
    </section>
</main>
<script>
    (function () {
        var form = document.getElementById('career-quiz');
        if (!form) {
            return;
        }

        var resultBox = document.getElementById('career-quiz-result');
        var progressBox = document.getElementById('career-quiz-progress');
        var statusBox = document.getElementById('career-quiz-status');
        var resetBtn = document.getElementById('career-quiz-reset');
        var questions = Array.prototype.slice.call(form.querySelectorAll('.quiz-question'));
        var storageKey = form.getAttribute('data-storage-key') || 'khokan-career-path-quiz-v1';
        var totalQuestions = questions.length;
        var resultParams = ['career_track', 'career_react', 'career_flutter'];
        var lastResult = null;

        function createElement(tag, className, text) {
            var element = document.createElement(tag);
            if (className) {
                element.className = className;
            }
            if (typeof text === 'string') {
                element.textContent = text;
            }
            return element;
        }

        function storageGet() {
            try {
                return window.localStorage ? window.localStorage.getItem(storageKey) : null;
            } catch (error) {
                return null;
            }
        }

        function storageSet(value) {
            try {
                if (window.localStorage) {
                    window.localStorage.setItem(storageKey, value);
                }
            } catch (error) {
            }
        }

        function storageRemove() {
            try {
                if (window.localStorage) {
                    window.localStorage.removeItem(storageKey);
                }
            } catch (error) {
            }
        }

        function readState() {
            var raw = storageGet();
            if (!raw) {
                return { answers: {} };
            }

            try {
                var parsed = JSON.parse(raw);
                if (parsed && typeof parsed === 'object') {
                    return parsed;
                }
            } catch (error) {
            }

            return { answers: {} };
        }

        function getAnswers() {
            var answers = {};

            questions.forEach(function (question) {
                var checked = question.querySelector('input[type="radio"]:checked');
                if (checked) {
                    answers[checked.name] = checked.value;
                }
            });

            return answers;
        }

        function writeState() {
            var payload = {
                answers: getAnswers()
            };

            if (lastResult) {
                payload.result = lastResult;
            }

            storageSet(JSON.stringify(payload));
        }

        function restoreAnswers(answers) {
            Object.keys(answers || {}).forEach(function (name) {
                var value = answers[name];
                var selector = 'input[name="' + name + '"][value="' + value + '"]';
                var input = form.querySelector(selector);
                if (input) {
                    input.checked = true;
                }
            });
        }

        function countAnswered() {
            return Object.keys(getAnswers()).length;
        }

        function updateProgress() {
            var answered = countAnswered();

            if (progressBox) {
                progressBox.textContent = 'Answered: ' + answered + '/' + totalQuestions;
            }

            if (statusBox) {
                statusBox.textContent = answered === totalQuestions
                    ? 'সব answer হয়েছে. এখন result দেখো.'
                    : '2-3 minute-এ শেষ হবে.';
            }
        }

        function clearResultParams() {
            var url = new URL(window.location.href);
            var changed = false;

            resultParams.forEach(function (key) {
                if (url.searchParams.has(key)) {
                    url.searchParams.delete(key);
                    changed = true;
                }
            });

            if (changed) {
                window.history.replaceState({}, '', url.toString());
            }
        }

        function hideResult(options) {
            if (resultBox) {
                resultBox.hidden = true;
                resultBox.replaceChildren();
            }

            lastResult = null;

            if (!options || options.clearUrl !== false) {
                clearResultParams();
            }
        }

        function showMessage(message) {
            hideResult({ clearUrl: false });

            if (!resultBox) {
                return;
            }

            resultBox.hidden = false;
            resultBox.replaceChildren();
            resultBox.appendChild(createElement('p', '', message));
        }

        function buildResultData(reactScore, flutterScore) {
            var isTie = reactScore === flutterScore;
            var track = reactScore >= flutterScore ? 'react' : 'flutter';
            var trackTitle = track === 'react' ? 'React Track Recommended' : 'Flutter Track Recommended';
            var trackLabel = track === 'react' ? 'React' : 'Flutter';
            var reason = '';

            if (track === 'react') {
                reason = isTie
                    ? 'দুই track-এই তোমার interest আছে, but React দিয়ে start করলে দ্রুত web portfolio build, remote/freelancing target, আর lightweight setup ধরে momentum তোলা সহজ হবে।'
                    : 'তোমার answers দেখাচ্ছে web products, browser-based work, আর remote/freelancing direction তোমার সাথে বেশি match করছে। তাই React track তোমার জন্য better fit।';
            } else {
                reason = 'তোমার answers দেখাচ্ছে mobile-first product thinking, app experience, আর Bangladesh/local startup job track তোমার সাথে বেশি match করছে। তাই Flutter track তোমার জন্য stronger choice।';
            }

            var shareUrl = new URL(window.location.href);
            resultParams.forEach(function (key) {
                shareUrl.searchParams.delete(key);
            });
            shareUrl.searchParams.set('career_track', track);
            shareUrl.searchParams.set('career_react', String(reactScore));
            shareUrl.searchParams.set('career_flutter', String(flutterScore));

            return {
                track: track,
                trackTitle: trackTitle,
                trackLabel: trackLabel,
                reactScore: reactScore,
                flutterScore: flutterScore,
                isTie: isTie,
                reason: reason,
                discountCode: 'QUIZ1000',
                discountLabel: 'BDT 1,000 off',
                applyUrl: track === 'react'
                    ? (form.getAttribute('data-apply-react') || '')
                    : (form.getAttribute('data-apply-flutter') || ''),
                shareUrl: shareUrl.toString()
            };
        }

        function calculateScores() {
            var reactScore = 0;
            var flutterScore = 0;

            questions.forEach(function (question) {
                var checked = question.querySelector('input[type="radio"]:checked');
                if (!checked) {
                    return;
                }

                reactScore += parseInt(checked.getAttribute('data-react') || '0', 10);
                flutterScore += parseInt(checked.getAttribute('data-flutter') || '0', 10);
            });

            return buildResultData(reactScore, flutterScore);
        }

        function renderResult(data, options) {
            options = options || {};
            lastResult = data;

            if (!resultBox) {
                return;
            }

            resultBox.hidden = false;
            resultBox.replaceChildren();

            var heading = createElement('div', 'section-heading');
            heading.appendChild(createElement('h2', '', data.trackTitle));
            heading.appendChild(createElement(
                'p',
                'section-subtitle',
                data.isTie
                    ? 'Balanced score এসেছে, but শুরুতে one clear path নিলে faster progress হবে।'
                    : 'এই track-এ তোমার answers stronger match দেখাচ্ছে।'
            ));
            resultBox.appendChild(heading);

            var grid = createElement('div', 'course-grid course-grid--two');

            var summaryCard = createElement('div', 'course-card');
            summaryCard.appendChild(createElement('div', 'quiz-discount', 'Recommended Track: ' + data.trackLabel));

            var scoreMeta = createElement('div', 'quiz-meta');
            scoreMeta.appendChild(createElement('span', 'quiz-meta__item', 'React Score: ' + data.reactScore));
            scoreMeta.appendChild(createElement('span', 'quiz-meta__item', 'Flutter Score: ' + data.flutterScore));
            summaryCard.appendChild(scoreMeta);

            summaryCard.appendChild(createElement('h3', '', 'Why this fits you'));
            summaryCard.appendChild(createElement('p', 'section-subtitle', data.reason));
            grid.appendChild(summaryCard);

            var offerCard = createElement('div', 'course-card');
            offerCard.appendChild(createElement('div', 'quiz-discount', 'Discount Code: ' + data.discountCode));
            offerCard.appendChild(createElement('h3', '', 'Use this on enrollment'));
            offerCard.appendChild(createElement('p', 'section-subtitle', 'এই quiz complete করলে তুমি ' + data.discountLabel + ' পাবে। Apply করার সময় codeটা mention করো: ' + data.discountCode + '.'));

            var shareLabel = createElement('p', 'section-subtitle', 'Share this result link:');
            offerCard.appendChild(shareLabel);

            var shareInput = createElement('input', 'quiz-input');
            shareInput.type = 'text';
            shareInput.readOnly = true;
            shareInput.value = data.shareUrl;
            offerCard.appendChild(shareInput);

            var actions = createElement('div', 'quiz-actions');

            if (data.applyUrl) {
                var applyLink = createElement('a', 'primary-btn', 'Apply Now');
                applyLink.href = data.applyUrl;
                actions.appendChild(applyLink);
            }

            var copyBtn = createElement('button', 'secondary-btn', 'Copy Result Link');
            copyBtn.type = 'button';
            copyBtn.addEventListener('click', function () {
                var originalText = copyBtn.textContent;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(shareInput.value).then(function () {
                        copyBtn.textContent = 'Link Copied';
                        setTimeout(function () {
                            copyBtn.textContent = originalText;
                        }, 1600);
                    }).catch(function () {
                        shareInput.focus();
                        shareInput.select();
                    });
                    return;
                }

                shareInput.focus();
                shareInput.select();
            });
            actions.appendChild(copyBtn);

            offerCard.appendChild(actions);
            grid.appendChild(offerCard);

            resultBox.appendChild(grid);

            if (options.updateUrl !== false) {
                window.history.replaceState({}, '', data.shareUrl);
            }

            if (options.saveState !== false) {
                writeState();
            }

            if (options.scroll !== false) {
                resultBox.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        function readResultFromUrl() {
            var url = new URL(window.location.href);
            var reactScore = parseInt(url.searchParams.get('career_react') || '', 10);
            var flutterScore = parseInt(url.searchParams.get('career_flutter') || '', 10);
            var track = url.searchParams.get('career_track') || '';

            if ((track !== 'react' && track !== 'flutter') || Number.isNaN(reactScore) || Number.isNaN(flutterScore)) {
                return null;
            }

            return buildResultData(reactScore, flutterScore);
        }

        form.addEventListener('change', function () {
            updateProgress();

            if (lastResult) {
                hideResult();
            }

            writeState();
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (countAnswered() !== totalQuestions) {
                showMessage('সব 7টা question answer করো, তারপর তোমার recommended track দেখানো হবে।');
                writeState();
                return;
            }

            renderResult(calculateScores());
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                form.reset();
                storageRemove();
                hideResult();
                updateProgress();
            });
        }

        var savedState = readState();
        restoreAnswers(savedState.answers || {});
        updateProgress();

        var sharedResult = readResultFromUrl();
        if (sharedResult) {
            renderResult(sharedResult, { updateUrl: false });
            return;
        }

        if (savedState.result) {
            renderResult(savedState.result, { updateUrl: false, scroll: false });
        }
    })();
</script>
<?php get_footer(); ?>
