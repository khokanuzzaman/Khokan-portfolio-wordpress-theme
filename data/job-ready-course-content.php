<?php
return [
    'hero' => [
        'title' => 'Modern Developer Program — Job Transformation Track',
        'subtitle' => 'Last semester student বা fresh graduate? এখনই skill, portfolio, interview-ready হতে হবে। এটা কোনো tutorial course না; এটা একটি Job Transformation Program।',
        'note' => 'Focus: Foundation → Primary Track → AI Workflow → Interview Prep',
        'ctas' => [
            [
                'label' => '{{BATCH_NAME}} Apply করুন',
                'link' => '{{GOOGLE_FORM_LINK}}',
                'class' => 'primary-btn',
            ],
            [
                'label' => 'ওয়েবসাইটে Enroll',
                'link' => '{{WEBSITE_ENROLL_LINK}}',
                'class' => 'secondary-btn',
            ],
        ],
        'card' => [
            'Start Date' => '{{START_DATE}}',
            'Batch Size' => '{{SEAT_LIMIT}}',
            'Fee From' => '{{EARLY_BIRD_FEE}}',
            'Mode' => '{{DELIVERY_MODE}}',
        ],
        'card_display' => [
            'শুরু' => '{{START_DATE}}',
            'ক্লাস শিডিউল' => '{{CLASS_SCHEDULE}}',
            'ডিউরেশন' => '{{BATCH_DURATION}}',
            'সিট' => '{{SEAT_LIMIT}}',
            'রেগুলার ফি' => '{{REGULAR_FEE}}',
            'Early Bird' => '{{EARLY_BIRD_FEE}}',
        ],
        'microcopy' => 'Seats limited: {{SEAT_LIMIT}} | Early Bird শেষ হবে {{EARLY_BIRD_DEADLINE}}',
    ],
    'why' => [
        'title' => 'কেন এই Program',
        'subtitle' => 'Skill gap আছে — সমাধানও হতে হবে real workflow দিয়ে',
        'content' => 'বাংলাদেশে অনেক graduate coding জানে, কিন্তু real developer workflow জানে না। Interview তে fail হয় কারণ proof নেই। এই program আপনাকে tutorial mindset থেকে বের করে job-ready developer বানানোর জন্য।',
        'bullets' => [
            'Problem: Shallow skill, no portfolio, no interview confidence',
            'Solution: Structured roadmap + real projects + AI discipline',
            'Goal: Recruiter-ready proof, শুধু certificate না',
        ],
        'cta' => 'Structure দেখুন',
        'microcopy' => 'No fake promises. Realistic, repeatable progress.',
    ],
    'before_after' => [
        'title' => 'Before vs After Transformation',
        'subtitle' => 'No hype, measurable change',
        'rows' => [
            ['before' => 'Random tutorials', 'after' => 'Clear roadmap + milestones'],
            ['before' => 'Weak GitHub', 'after' => 'Recruiter-ready GitHub'],
            ['before' => 'Fear of interview', 'after' => 'Project confidence + mock practice'],
            ['before' => 'No live demos', 'after' => 'Deployed projects with README'],
            ['before' => 'AI misuse', 'after' => 'Ethical AI workflow'],
        ],
    ],
    'audience' => [
        'title' => 'এই Program কার জন্য',
        'items' => [
            'Last semester CS/CSE/IT students',
            'Fresh graduates (0–1 year)',
            'যারা one primary track deeply শিখতে চায়',
            'Portfolio + interview focus করতে চায়',
        ],
    ],
    'not_for' => [
        'title' => 'কারা Join করবেন না',
        'items' => [
            'যারা shortcut বা fake guarantee চায়',
            'যারা weekly consistent সময় দিতে পারবে না',
            'যারা শুধু theory শিখতে চায়, project না',
        ],
    ],
    'structure' => [
        'title' => '4-Phase Program Structure',
        'subtitle' => 'Foundation → Track → AI Workflow → Interview',
        'phases' => [
            [
                'title' => 'Foundation',
                'weeks' => '{{FOUNDATION_WEEKS}} weeks',
                'items' => [
                    'Core programming habits',
                    'Git, GitHub, এবং dev tools',
                    'Problem solving mindset',
                ],
            ],
            [
                'title' => 'Primary Track',
                'weeks' => '{{TRACK_WEEKS}} weeks',
                'items' => [
                    'একটা track এ deep skill build',
                    'Project-based learning',
                    'Code review + feedback loop',
                ],
            ],
            [
                'title' => 'AI Workflow',
                'weeks' => 'Integrated',
                'items' => [
                    'Ethical AI usage rules',
                    'Prompting for learning, not copying',
                    'Refactor + debugging with AI',
                ],
            ],
            [
                'title' => 'Interview Prep',
                'weeks' => '{{INTERVIEW_WEEKS}} weeks',
                'items' => [
                    'Mock interview + feedback',
                    'Project storytelling practice',
                    'CV + LinkedIn polish',
                ],
            ],
        ],
    ],
    'tracks' => [
        'title' => 'Choose Your Primary Track',
        'subtitle' => 'একটা primary focus. Deep skill.',
        'items' => [
            [
                'title' => 'Mobile Engineer (Flutter)',
                'items' => [
                    'Dart + Flutter core',
                    'State management + API',
                    'App architecture & deployment',
                    'Portfolio mobile projects',
                ],
                'cta' => 'Flutter Track নিন',
            ],
            [
                'title' => 'Web Engineer (React)',
                'items' => [
                    'React fundamentals',
                    'Hooks, routing, API integration',
                    'Modern frontend patterns',
                    'Portfolio web projects',
                ],
                'cta' => 'React Track নিন',
            ],
        ],
    ],
    'ai_usage' => [
        'title' => 'AI-Assisted Developer Workflow',
        'subtitle' => 'Ethical + practical, no blind copy',
        'rules' => [
            'AI হলো learning partner, shortcut না',
            'ব্যবহার করার আগে বুঝতে হবে',
            'AI output docs ও best practices এর সাথে মিলিয়ে দেখুন',
            'Final solution আপনার নিজের হবে',
        ],
        'good' => [
            'Error message explain করতে বলা',
            '২–৩টা solution option নেয়া',
            'Test data generate করা',
            'Code review checklist নেয়া',
        ],
        'bad' => [
            'বোঝা ছাড়া full project code copy',
            'AI-made code নিজের বলে submit',
        ],
        'cta' => [
            'title' => 'AI workflow guide চান?',
            'subtitle' => 'একটা free guideline call থেকে শুরু করুন।',
        ],
    ],
    'projects' => [
        'title' => 'Projects You Will Build',
        'subtitle' => 'Portfolio-ready proof, demo-only না',
        'items' => [
            '{{PROJECT_1}}',
            '{{PROJECT_2}}',
            '{{PROJECT_3}}',
            '{{PROJECT_4}}',
            '{{PROJECT_5}}',
        ],
    ],
    'portfolio_support' => [
        'title' => 'Portfolio & Professional Setup Support — Career Identity Launch',
        'subtitle' => 'এটা শুধু projects বানানো না—এটা আপনার developer identity কে live করে তোলার guided, career-level support।',
        'launch_title' => 'আপনি যা পাবেন',
        'launch_items' => [
            'Personal developer portfolio website',
            'Custom domain কেনার guidance (low-cost options)',
            'GitHub Pages hosting option (free hosting possibility)',
            'Affordable hosting provider option (budget-friendly)',
            'Domain + hosting connect করার support',
            'SSL setup guidance (secure https)',
            'Resume integration (download section)',
            'Live project showcase',
            'GitHub integration',
            'LinkedIn integration',
            'Contact form setup',
            'Mobile responsive layout',
        ],
        'tech_title' => 'Technical Setup Support (Guided)',
        'tech_items' => [
            'Portfolio structure + layout guidance',
            'GitHub deployment + portfolio publishing support',
            'Domain DNS pointing + live site check',
            'Quality checklist + final review',
        ],
        'domain_title' => 'Domain + Hosting Setup',
        'domain_items' => [
            'Domain selection guidance (budget-friendly)',
            'Hosting options for portfolio sites',
            'Domain + hosting connect assistance',
            'SSL enablement + security basics',
        ],
        'payment_title' => 'International Payment Assistance (USD)',
        'payment_items' => [
            'USD দিয়ে domain purchase করার guidance',
            'Hosting purchase assistance (USD)',
            'Paid AI tools setup support (ChatGPT, Cursor, etc.)',
            'Payment method guidance (cards, virtual cards, etc.)',
            'Ethical + legal payment direction',
        ],
        'tools_title' => 'AI Tools & Dev Tools Support',
        'tools_items' => [
            'AI tool subscription guidance (ChatGPT, Cursor, etc.)',
            'Developer tools environment setup',
            'Ethical AI usage discipline',
            'Long-term productivity workflow',
        ],
        'interview_title' => 'Interview Advantage Positioning',
        'interview_items' => [
            'Recruiter কে নিজের domain পাঠাতে পারবেন',
            '“I am learning” বলা বন্ধ—live proof দেখাবেন',
            'Projects live দেখিয়ে confidence build করবেন',
        ],
        'dates_line' => 'Program Start: 5–10 April | Early Bird Enrollment Deadline: Until Ramadan | Seats: {{SEAT_LIMIT}}',
        'cta_text' => 'Launch My Portfolio',
        'microcopy' => 'Early Bird fee: {{EARLY_BIRD_FEE}} | Apply: {{GOOGLE_FORM_LINK}}',
    ],
    'interview' => [
        'title' => 'Interview Advantage Framework',
        'subtitle' => 'Professional ভাবে আপনার কাজ দেখাতে শিখবেন',
        'items' => [
            'GitHub structure এবং repo hygiene',
            'Recruiter-friendly README লেখা',
            'Deployment + live demo',
            'Mock interview practice',
            'Project explanation skill',
            'AI usage discipline',
        ],
        'cta' => 'Interview Prep দেখুন',
    ],
    'mentors' => [
        'title' => 'Mentor Panel',
        'subtitle' => 'Industry mentor দের সাথে direct support',
        'items' => [
            [
                'name' => '{{MENTOR_NAME_1}}',
                'role' => '{{MENTOR_ROLE_1}}',
                'experience' => '{{YEARS_EXPERIENCE_1}}',
                'specialization' => '{{SPECIALIZATION_1}}',
                'projects' => '{{REAL_PROJECTS_1}}',
                'linkedin' => '{{LINKEDIN_1}}',
                'github' => '{{GITHUB_1}}',
                'message' => '{{SHORT_MESSAGE_1}}',
            ],
            [
                'name' => '{{MENTOR_NAME_2}}',
                'role' => '{{MENTOR_ROLE_2}}',
                'experience' => '{{YEARS_EXPERIENCE_2}}',
                'specialization' => '{{SPECIALIZATION_2}}',
                'projects' => '{{REAL_PROJECTS_2}}',
                'linkedin' => '{{LINKEDIN_2}}',
                'github' => '{{GITHUB_2}}',
                'message' => '{{SHORT_MESSAGE_2}}',
            ],
        ],
        'cta' => 'Mentors দেখুন',
    ],
    'timeline' => [
        'title' => 'Week-by-Week Roadmap',
        'subtitle' => 'Clear progression, no confusion',
        'weeks' => [
            'Week 1–2: Fundamentals + tools',
            'Week 3–5: Primary track core',
            'Week 6–8: Advanced + projects',
            'Week 9–10: AI workflow + refactor',
            'Week 11–12: Interview prep + mock',
        ],
    ],
    'pricing' => [
        'title' => 'Pricing & Options',
        'subtitle' => 'Student-friendly, transparent',
        'standard' => '{{REGULAR_FEE}}',
        'early' => '{{EARLY_BIRD_FEE}}',
        'installment' => '{{INSTALLMENT_OPTION}}',
        'note' => 'Early Bird শেষ হবে {{EARLY_BIRD_DEADLINE}}',
        'cta' => 'Seat বুক করুন',
    ],
    'enrollment' => [
        'title' => 'Enrollment Options',
        'subtitle' => 'যে মাধ্যমে সহজ, সেটা নিন',
        'google_form' => [
            'title' => 'Google Form দিয়ে Apply',
            'link' => '{{GOOGLE_FORM_LINK}}',
            'button' => 'Apply via Google Form',
            'lines' => [
                'Basic info দিন seat request এর জন্য',
                'Eligibility review করে confirm করা হবে',
                'Confirmation পেতে সময়: {{CONFIRMATION_TIME}}',
            ],
            'trust' => 'আপনার তথ্য শুধু admission এর জন্য ব্যবহার হবে।',
            'after_submit' => 'Thanks. {{CONFIRMATION_TIME}} এর মধ্যে আমরা contact করব।',
        ],
        'website' => [
            'title' => 'ওয়েবসাইটে Enroll',
            'link' => '{{WEBSITE_ENROLL_LINK}}',
            'button' => 'Confirm My Seat',
            'fields' => [
                'Full name',
                'Email',
                'Phone',
                'University',
                'Track choice',
                'Payment option',
            ],
            'success' => 'আপনি pre-approved. Payment instructions পাঠানো হবে।',
            'privacy' => 'আমরা আপনার তথ্য শেয়ার করি না।',
            'urgency' => 'Seats left: {{SEAT_LIMIT}}',
        ],
    ],
    'faq_title' => 'FAQ',
    'faq' => [
        [
            'q' => 'Program duration কত?',
            'a' => '{{BATCH_DURATION}}',
        ],
        [
            'q' => 'Weekly class schedule?',
            'a' => '{{CLASS_SCHEDULE}}',
        ],
        [
            'q' => 'Online নাকি offline?',
            'a' => '{{DELIVERY_MODE}}',
        ],
        [
            'q' => 'Job guarantee আছে?',
            'a' => 'No. We focus on skill + portfolio + interview readiness.',
        ],
        [
            'q' => 'Early Bird fee কতদিন থাকবে?',
            'a' => '{{EARLY_BIRD_DEADLINE}}',
        ],
        [
            'q' => 'Installment possible?',
            'a' => '{{INSTALLMENT_OPTION}}',
        ],
        [
            'q' => 'Laptop requirement?',
            'a' => '{{LAPTOP_REQUIREMENT}}',
        ],
        [
            'q' => 'Prior coding লাগবে?',
            'a' => 'Basic required, foundation covered in program.',
        ],
        [
            'q' => 'AI tools access?',
            'a' => '{{AI_TOOLS_LIST}}',
        ],
        [
            'q' => 'Drop করলে refund?',
            'a' => '{{REFUND_POLICY}}',
        ],
        [
            'q' => 'Mentor support কিভাবে?',
            'a' => 'Weekly live sessions + project review.',
        ],
        [
            'q' => 'Project deployment শেখাবে?',
            'a' => 'Yes, live demos included.',
        ],
    ],
    'final_cta' => [
        'title' => 'Ready to Transform into a Modern Developer?',
        'subtitle' => 'Next batch starts {{START_DATE}} — seats only {{SEAT_LIMIT}}',
        'cta' => 'Apply Now',
        'microcopy' => 'No fake promises. Real skills, real projects.',
    ],
    'learning' => [
        'title' => 'What You Will Learn',
        'subtitle' => 'Four tracks, structured and beginner-first.',
        'tracks' => [
            [
                'title' => 'Flutter (Mobile)',
                'items' => [
                    'Dart + Flutter core',
                    'State management + API',
                    'App architecture & deployment',
                    'Portfolio mobile projects',
                ],
            ],
            [
                'title' => 'React (Web)',
                'items' => [
                    'React fundamentals',
                    'Hooks, routing, API integration',
                    'Modern frontend patterns',
                    'Portfolio web projects',
                ],
            ],
            [
                'title' => 'AI (Practical & Ethical)',
                'items' => [
                    'Prompting for learning, not copying',
                    'Debugging help and refactor suggestions',
                    'AI as learning partner, not cheating',
                ],
            ],
            [
                'title' => 'Job Prep',
                'items' => [
                    'GitHub profile setup',
                    'CV + portfolio guidance',
                    'Interview practice (basic)',
                ],
            ],
        ],
    ],
    'details' => [
        'title' => 'Course Details',
        'items' => [
            'Duration' => '{{BATCH_DURATION}}',
            'Class Days/Week' => '{{CLASS_SCHEDULE}}',
            'Mode' => '{{DELIVERY_MODE}}',
            'Language' => 'Bangla + English mix',
            'Batch Size' => '{{SEAT_LIMIT}}',
            'Start Date' => '{{START_DATE}}',
            'Class Time' => '{{CLASS_TIME_OPTIONS}}',
        ],
    ],
    'fee' => [
        'title' => 'Fee (Student-Friendly)',
        'standard' => '{{REGULAR_FEE}}',
        'early' => '{{EARLY_BIRD_FEE}}',
        'installment' => '{{INSTALLMENT_OPTION}}',
        'note' => 'Early Bird ends: {{EARLY_BIRD_DEADLINE}}',
    ],
    'mentor' => [
        'title' => 'Mentor',
        'bio' => 'Mentor panel available. See below for multi-mentor details.',
    ],
];
