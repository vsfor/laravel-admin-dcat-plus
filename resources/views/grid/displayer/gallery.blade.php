<style>
    .dengje-gallery-group {
        height: {{ $height }}px;
        width: {{ $width }}px;
        font-size: {{ $width / 12 }};
    }
</style>

<div id="{{ $id }}" class="dengje-gallery-group {{ count($src) > 1 ? 'multiple' : '' }}">
    <div class="gallery-img-wrapper bg-multi bg-left"></div>
    <div class="gallery-img-wrapper bg-multi bg-right"></div>
    <div class="gallery-img-wrapper">
        @foreach ($src as $k => $v)
            <img src="{{ $v }}" class="{{ $k >= 1 ? 'hide' : '' }}" alt="">
        @endforeach
    </div>
</div>

<script require="@grid-column-gallery-asset">
    var image = new Viewer(document.getElementById('{{ $id }}'));
</script>
