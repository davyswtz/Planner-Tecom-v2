@props([
    'tipoPlaceholder' => 'Ex: REPARO DE CABO',
    'statusVariant' => 'rompimento',
])

@php
  $statusOptions = $statusVariant === 'rompimento'
    ? [
        ['value' => 'aberta', 'label' => 'Aberta'],
        ['value' => 'em_andamento', 'label' => 'Em andamento'],
        ['value' => 'finalizada', 'label' => 'Finalizada'],
      ]
    : [
        ['value' => 'Aberta', 'label' => 'Aberta'],
        ['value' => 'Em andamento', 'label' => 'Em andamento'],
        ['value' => 'Finalizada', 'label' => 'Finalizada'],
      ];
@endphp

<x-modal
  id="modal-os-overlay"
  titulo="Nova Ordem de Serviço"
  titulo-id="os-modal-titulo"
  subtitulo-id="os-modal-sub"
  fechar="fecharNovaOS()">

  <div class="os-field">
    <label class="os-label">Tipo de serviço</label>
    <input type="text" id="os-input-tipo" class="os-input"
      placeholder="{{ $tipoPlaceholder }}"
      oninput="this.value = this.value.toUpperCase()"/>
  </div>

  <div class="os-field">
    <label class="os-label">Descrição</label>
    <textarea id="os-input-descricao" rows="3" placeholder="Descreva a ordem de serviço..." class="os-input"
      style="height:auto;padding:8px 10px;resize:vertical;min-height:72px"></textarea>
  </div>

  <div class="os-field">
    <label class="os-label">Anexos</label>
    <div id="os-anexos-galeria" class="os-anexos-galeria">
      <div class="os-anexos-vazio">Nenhum anexo vinculado a esta OS.</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="os-field">
      <label class="os-label">Técnicos responsáveis</label>
      <div id="os-tecnicos-wrap" style="position:relative">
        <div id="os-tecnicos-tags" class="os-input"
          onclick="window.toggleOsTecnicosDropdown?.()"
          style="display:flex;flex-wrap:wrap;gap:4px;min-height:38px;align-items:center;cursor:pointer;height:auto;padding:6px 10px">
          <span id="os-tecnicos-placeholder" style="color:var(--gray-400);font-size:13px">Selecione...</span>
        </div>
        <div id="os-tecnicos-dropdown"
          style="display:none;position:absolute;top:100%;left:0;right:0;margin-top:4px;background:var(--white);
                 border:1px solid var(--gray-200);border-radius:var(--radius-sm);z-index:300;
                 max-height:180px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,0.1)">
        </div>
      </div>
    </div>
    <div class="os-field">
      <label class="os-label">Status</label>
      <select id="os-input-status" class="os-input">
        @foreach ($statusOptions as $opcao)
          <option value="{{ $opcao['value'] }}">{{ $opcao['label'] }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <x-slot name="footer">
    <div class="modal-foot-os">
      <div class="modal-foot-os-left">
        <button type="button" class="os-btn-anexo-round" id="os-btn-anexo" title="Anexar imagem">
          <i class="ti ti-paperclip"></i>
        </button>
        <input type="file" id="os-input-anexo" accept="image/*" multiple hidden />
      </div>
      <div class="modal-foot-os-actions">
        <button type="button" onclick="fecharNovaOS()" class="btn-modal btn-modal-ghost">Cancelar</button>
        <button type="button" class="btn-modal btn-modal-primary" id="os-btn-salvar" onclick="salvarOs()">
          <i class="ti ti-clipboard-check" style="font-size:14px" id="os-btn-icon"></i>
          <span id="os-btn-label">Criar OS</span>
        </button>
      </div>
    </div>
  </x-slot>

</x-modal>
