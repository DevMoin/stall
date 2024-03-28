<?php
function getStyles()
{

    $styles = [
        'reset' => "\e[0m",
        'bold' => "\e[1m",
        'dim' => "\e[2m",
        'italic' => "\e[3m",
        'underline' => "\e[4m",
        'blink' => "\e[5m",
        'reverse' => "\e[7m",
        'hidden' => "\e[8m",
        'strikethrough' => "\e[9m",
        'default' => "\e[39m",
        'black' => "\e[30m",
        'red' => "\e[31m",
        'green' => "\e[32m",
        'yellow' => "\e[33m",
        'blue' => "\e[34m",
        'magenta' => "\e[35m",
        'cyan' => "\e[36m",
        'light_gray' => "\e[37m",
        'dark_gray' => "\e[90m",
        'light_red' => "\e[91m",
        'light_green' => "\e[92m",
        'light_yellow' => "\e[93m",
        'light_blue' => "\e[94m",
        'light_magenta' => "\e[95m",
        'light_cyan' => "\e[96m",
        'white' => "\e[97m",
        'bg_default' => "\e[49m",
        'bg_black' => "\e[40m",
        'bg_red' => "\e[41m",
        'bg_green' => "\e[42m",
        'bg_yellow' => "\e[43m",
        'bg_blue' => "\e[44m",
        'bg_magenta' => "\e[45m",
        'bg_cyan' => "\e[46m",
        'bg_light_gray' => "\e[47m",
        'bg_dark_gray' => "\e[100m",
        'bg_light_red' => "\e[101m",
        'bg_light_green' => "\e[102m",
        'bg_light_yellow' => "\e[103m",
        'bg_light_blue' => "\e[104m",
        'bg_light_magenta' => "\e[105m",
        'bg_light_cyan' => "\e[106m",
        'bg_white' => "\e[107m",
    ];
    return $styles;
}

function st($text, ...$styles)
{
    $style = "";
    if(is_array($styles)){
        foreach($styles as $st){
            $style .= style($st);
        }
    }else{
        $style = style("reset");
    }
    return $style.$text.style("reset");
}

function style($key='reset'){
    static $styles;
    if(!$styles)
    {
        $styles = getStyles();
    }
    return isset($styles[$key])?$styles[$key]:'';
}