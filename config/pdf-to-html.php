<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDF to HTML Conversion Settings
    |--------------------------------------------------------------------------
    |
    | These settings control how PDFs are converted to HTML format.
    |
    */

    // Image extraction settings
    'images' => [
        'enabled' => true,
        'quality' => 90,
        'dpi' => 300,
        'format' => 'png',
        'storage_path' => 'pdf-images',
    ],

    // Style settings
    'styles' => [
        'preserve_fonts' => true,
        'preserve_colors' => true,
        'preserve_positioning' => true,
        'default_font' => 'Arial',
        'min_heading_size' => 14,
    ],

    // Page settings
    'page' => [
        'max_width' => 1200,
        'margin' => 20,
        'background' => '#ffffff',
        'shadow' => true,
    ],

    // Output settings
    'output' => [
        'responsive' => true,
        'print_friendly' => true,
        'include_styles' => true,
    ],

    // CSS classes
    'css_classes' => [
        'container' => 'pdf-container',
        'page' => 'pdf-page',
        'content' => 'pdf-content',
        'text' => 'pdf-text',
        'image' => 'pdf-image',
        'heading' => 'pdf-heading',
    ],

    // Responsive breakpoints
    'breakpoints' => [
        'mobile' => 768,
        'tablet' => 1024,
    ],
]; 