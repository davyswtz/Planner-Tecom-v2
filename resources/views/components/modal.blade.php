@props([
    'id' => 'modal-overlay',
    'titulo' => '',
    'subtitulo' => '',
    'tituloId' => null,
    'subtituloId' => null,
    'fechar' => 'fecharModal()',
    'tabs' => false,
])

<div id="{{ $id }}" class="modal-overlay">
  <div class="modal-box">

    <div class="modal-head">
      <div>
        <p class="modal-title" @if($tituloId) id="{{ $tituloId }}" @endif>{{ $titulo }}</p>
        <p class="modal-sub" @if($subtituloId) id="{{ $subtituloId }}" @endif>{{ $subtitulo }}</p>
      </div>
      <button onclick="{{ $fechar }}" class="modal-close">
        <i class="ti ti-x"></i>
      </button>
    </div>

    @if($tabs)
      {{ $tabs }}
    @endif

    <div class="modal-body" style="display:flex;flex-direction:column;gap:16px">
      {{ $slot }}
    </div>

    <div class="modal-foot">
      {{ $footer }}
    </div>

  </div>
</div>