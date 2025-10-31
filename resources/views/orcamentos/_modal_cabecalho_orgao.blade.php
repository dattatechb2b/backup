{{-- Modal Cabeçalho do Órgão --}}
<div class="modal fade" id="modalCabecalhoOrgao" tabindex="-1" aria-labelledby="modalCabecalhoOrgaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: white;">
                <h5 class="modal-title" id="modalCabecalhoOrgaoLabel">
                    <i class="fas fa-landmark"></i> Cabeçalho do Órgão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body" style="padding: 24px;">
                <form id="form-cabecalho-orgao">
                    @csrf

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label" style="font-weight: 600; color: #1f2937;">
                                Razão Social <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="razao_social"
                                   id="razao_social"
                                   placeholder="Ex: Prefeitura Municipal de Natal"
                                   required
                                   style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px;">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label" style="font-weight: 600; color: #1f2937;">
                                CNPJ
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="cnpj"
                                   id="cnpj"
                                   placeholder="00.000.000/0000-00"
                                   maxlength="18"
                                   style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #1f2937;">
                            Nome Fantasia
                        </label>
                        <input type="text"
                               class="form-control"
                               name="nome_fantasia"
                               id="nome_fantasia"
                               placeholder="Ex: Prefeitura de Natal"
                               style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #1f2937;">
                            Endereço Completo
                        </label>
                        <input type="text"
                               class="form-control"
                               name="endereco"
                               id="endereco"
                               placeholder="Ex: Rua Tal, 123, Bairro Centro"
                               style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px;">
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label" style="font-weight: 600; color: #1f2937;">
                                CEP
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="cep"
                                   id="cep"
                                   placeholder="00000-000"
                                   maxlength="9"
                                   style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px;">
                        </div>

                        <div class="col-md-5 mb-3">
                            <label class="form-label" style="font-weight: 600; color: #1f2937;">
                                Cidade
                            </label>
                            <input type="text"
                                   class="form-control"
                                   name="cidade"
                                   id="cidade"
                                   placeholder="Ex: Natal"
                                   style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px;">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label" style="font-weight: 600; color: #1f2937;">
                                UF
                            </label>
                            <select class="form-select" name="uf" id="uf" style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px;">
                                <option value="">-</option>
                                <option value="AC">AC</option>
                                <option value="AL">AL</option>
                                <option value="AP">AP</option>
                                <option value="AM">AM</option>
                                <option value="BA">BA</option>
                                <option value="CE">CE</option>
                                <option value="DF">DF</option>
                                <option value="ES">ES</option>
                                <option value="GO">GO</option>
                                <option value="MA">MA</option>
                                <option value="MT">MT</option>
                                <option value="MS">MS</option>
                                <option value="MG">MG</option>
                                <option value="PA">PA</option>
                                <option value="PB">PB</option>
                                <option value="PR">PR</option>
                                <option value="PE">PE</option>
                                <option value="PI">PI</option>
                                <option value="RJ">RJ</option>
                                <option value="RN" selected>RN</option>
                                <option value="RS">RS</option>
                                <option value="RO">RO</option>
                                <option value="RR">RR</option>
                                <option value="SC">SC</option>
                                <option value="SP">SP</option>
                                <option value="SE">SE</option>
                                <option value="TO">TO</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #1f2937;">
                            <i class="fas fa-image"></i> Brasão / Logo (PNG, max 200KB)
                        </label>
                        <input type="file"
                               class="form-control"
                               name="brasao"
                               id="brasao"
                               accept="image/png"
                               style="border: 1px solid #d1d5db; border-radius: 6px; padding: 10px;">
                        <small class="text-muted" style="font-size: 11px;">
                            Recomendado: 200x200 pixels, fundo transparente
                        </small>
                    </div>

                    <div id="brasao-preview" style="display: none; margin-top: 16px; text-align: center;">
                        <img id="brasao-preview-img" src="" alt="Preview do Brasão" style="max-width: 150px; max-height: 150px; border: 2px solid #e5e7eb; border-radius: 8px; padding: 8px;">
                    </div>

                </form>
            </div>

            <div class="modal-footer" style="border-top: 1px solid #e5e7eb; padding: 16px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 8px 16px;">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btn-salvar-orgao" class="btn btn-primary" style="background: #2563eb; border: none; padding: 8px 16px;">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    console.log('[MODAL-ORGAO] Inicializando modal Cabeçalho do Órgão...');

    // Máscara CNPJ
    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });
    }

    // Máscara CEP
    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 8) {
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });
    }

    // Preview do Brasão
    const brasaoInput = document.getElementById('brasao');
    const brasaoPreview = document.getElementById('brasao-preview');
    const brasaoPreviewImg = document.getElementById('brasao-preview-img');

    if (brasaoInput) {
        brasaoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validar tamanho (max 200KB)
                if (file.size > 200 * 1024) {
                    alert('O arquivo deve ter no máximo 200KB.');
                    e.target.value = '';
                    brasaoPreview.style.display = 'none';
                    return;
                }

                // Validar tipo
                if (!file.type.match('image/png')) {
                    alert('Apenas arquivos PNG são aceitos.');
                    e.target.value = '';
                    brasaoPreview.style.display = 'none';
                    return;
                }

                // Mostrar preview
                const reader = new FileReader();
                reader.onload = function(event) {
                    brasaoPreviewImg.src = event.target.result;
                    brasaoPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                brasaoPreview.style.display = 'none';
            }
        });
    }

    // Botão Salvar
    const btnSalvar = document.getElementById('btn-salvar-orgao');
    if (btnSalvar) {
        btnSalvar.addEventListener('click', async function() {
            console.log('[MODAL-ORGAO] Salvando dados do órgão...');

            const form = document.getElementById('form-cabecalho-orgao');
            const formData = new FormData(form);

            // Validar razão social
            const razaoSocial = formData.get('razao_social');
            if (!razaoSocial || razaoSocial.trim() === '') {
                alert('Razão Social é obrigatória.');
                document.getElementById('razao_social').focus();
                return;
            }

            // Desabilitar botão durante processamento
            btnSalvar.disabled = true;
            const textoOriginal = btnSalvar.innerHTML;
            btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

            try {
                const response = await fetch('/api/orgaos', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    console.log('[MODAL-ORGAO] Órgão salvo com sucesso!', data);
                    alert('Órgão salvo com sucesso!');

                    // Fechar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalCabecalhoOrgao'));
                    if (modal) {
                        modal.hide();
                    }

                    // Limpar formulário
                    form.reset();
                    brasaoPreview.style.display = 'none';

                    // Recarregar página ou atualizar UI
                    if (data.orgao && data.orgao.id) {
                        // TODO: Atualizar seletor de órgão na página principal
                        location.reload();
                    }

                } else {
                    console.error('[MODAL-ORGAO] Erro ao salvar órgão:', data.message);
                    alert('Erro ao salvar órgão: ' + (data.message || 'Erro desconhecido'));
                }

            } catch (error) {
                console.error('[MODAL-ORGAO] Erro ao comunicar com o servidor:', error);
                alert('Erro ao comunicar com o servidor: ' + error.message);
            } finally {
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = textoOriginal;
            }
        });
    }

    console.log('[MODAL-ORGAO] ✅ Modal inicializado com sucesso!');

})();
</script>
