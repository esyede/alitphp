@include('head')
<center>
    <div class="content">
        <h1 class="title">{{ $title }}</h1>
        <p><a href="{{ $link }}">{{ $tagline }}</a></p>
        <small>v{{ $version }}</small>
    </div>
</center>
@include('foot')
