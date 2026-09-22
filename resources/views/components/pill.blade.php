@props(['tone' => 'info'])
<span {{ $attributes->merge(['class' => 'pill pill-'.$tone]) }}>{{ $slot }}</span>
