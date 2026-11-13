<?php

return [

    'appearance' => [
        'title' => 'Appearance',
        'subheading' => 'Choose how this panel looks for you. Your choice is saved to your account and applies on every device.',
        'close' => 'Close',
    ],

    'picker' => [
        'label' => 'Theme',
        'helper' => 'Changes apply right away.',
        'current' => 'Current',
        'default' => 'Panel default',
        'default_hint' => 'Follows :theme',
    ],

    'accent' => [
        'label' => 'Accent colour',
        'theme' => "Theme's colour",
        'custom' => 'Your own colour',
        'refused' => 'That colour cannot be used',
        'invalid' => 'Pick one of the colours, or a colour such as #4f46e5.',
        'too_light' => 'It is too light to stand out on a light page. Try a deeper shade.',
        'too_dark' => 'It is too dark to stand out in dark mode. Try a brighter shade.',
    ],

    'notifications' => [
        'saved' => 'Theme updated',
        'unavailable' => [
            'title' => ':theme is unavailable right now',
            'body' => 'This panel is shown in :default instead. You can pick another theme from Appearance.',
        ],
    ],

    'themes' => [
        'aurora' => [
            'label' => 'Aurora',
            'description' => 'Iridescent glass surfaces and a floating dock-style sidebar. Light by day, deep and calm in dark mode.',
        ],
        'midnight' => [
            'label' => 'Midnight',
            'description' => 'Dark-first with violet-to-cyan accents and soft glass panels. A modern SaaS feel.',
        ],
    ],

];
