<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CSS Classes and Styles
    |--------------------------------------------------------------------------
    |
    | Configure the CSS classes and styles for responsive HTML output
    |
    */
    'css_classes' => [
        'container' => 'pdf-content-container',
        'content' => 'pdf-content',
        'page' => 'pdf-page',
        'paragraph' => 'pdf-paragraph',
        'heading' => 'pdf-heading',
        'image' => 'pdf-image',
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive Breakpoints
    |--------------------------------------------------------------------------
    |
    | Define the breakpoints for responsive design (in pixels)
    |
    */
    'breakpoints' => [
        'mobile' => 480,
        'tablet' => 768,
        'desktop' => 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Format
    |--------------------------------------------------------------------------
    |
    | Configure the HTML output format settings
    |
    */
    'output' => [
        'include_styles' => true, // Whether to include default CSS styles
        'preserve_whitespace' => true,
        'split_paragraphs' => true,
        'responsive_images' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Styles
    |--------------------------------------------------------------------------
    |
    | Default CSS styles for responsive design
    |
    */
    'default_styles' => true, // Set to false to disable default styles
]; 