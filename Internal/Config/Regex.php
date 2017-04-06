<?php return
[
    //--------------------------------------------------------------------------------------------------
    // Regex
    //--------------------------------------------------------------------------------------------------
    //
    // Author     : Ozan UYKUN <ozanbote@windowslive.com> | <ozanbote@gmail.com>
    // Site       : www.znframework.com
    // License    : The MIT License
    // Copyright  : Copyright (c) 2012-2016, ZN Framework
    //
    //--------------------------------------------------------------------------------------------------

    //--------------------------------------------------------------------------------------------------
    // Regex Chars
    //--------------------------------------------------------------------------------------------------
    //
    // Genel Kullanımı: Düzenli ifadelerde yer alan özel karakterlerle ilgili aşağıdaki
    // değişiklikler yapılmıştır.
    //
    //--------------------------------------------------------------------------------------------------
    'regexChars' =>
    [
        // Patterns For Routes
        ':numeric'     => '(\d+$)',
        ':alnum'       => '(\w+$)',
        ':alpha'       => '([A-z]+$)',
        ':all'         => '(.+)',
        ':seo'         => '((\w+|\-)+$)',

        '{nonWord}'    => '\W+',
        '{word}'       => '\w+',
        '{nonNumeric}' => '\D',
        '{numeric}'    => '\d',
        '{schar}'      => '\W',
        '{nonSchar}'   => '\w',
        '{char}'       => '.',
        '{nonSpace}'   => '\S',
        '{space}'      => '\s',
        '{starting}'   => '^',
        '{ending}'     => '$',
        '{repeatZ}'    => '*',
        '{repeat}'     => '+',
        '{whether}'    => '?',
        '{or}'         => '|',
        '{eolR}'       => '\r',
        '{eolN}'       => '\n',
        '{eol}'        => '\r\n',
        '{tab}'        => '\t',
        '{esc}'        => '\e',
        '{hex}'        => '\x'
    ],

    //--------------------------------------------------------------------------------------------------
    // Setting Chars
    //--------------------------------------------------------------------------------------------------
    //
    // Genel Kullanımı: Düzenli ifadelerde oluşturulan desen sonuna konulan karakterlerle
    // ilgili aşağıdaki değişiklikler yapılmıştır
    //
    //--------------------------------------------------------------------------------------------------
    'settingChars' =>
    [
        '{insens}'    => 'i',
        '{generic}'   => 'g',
        '{each}'      => 's',
        '{multiline}' => 'm',
        '{inspace}'   => 'x'
    ],

    //--------------------------------------------------------------------------------------------------
    // Special Chars
    //--------------------------------------------------------------------------------------------------
    //
    // Genel Kullanımı: Düzenli ifadelerde yer alan özel karakterleri normal karakterler gibi
    // kullanmak için aşağıdaki değişiklikler yapılmıştır.
    //
    //--------------------------------------------------------------------------------------------------
    'specialChars' =>
    [
        '.' => '\.',
        '^' => '\^',
        '$' => '\$',
        '*' => '\*',
        '+' => '\+',
        '?' => '\?',
        '|' => '\|',
        '/' => '\/'
    ]
];
