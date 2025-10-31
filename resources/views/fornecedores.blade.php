@extends('layouts.app')

@section('title', 'Lista de Fornecedores')

@section('content')
<div style="padding: 25px;">
    <!-- Título da Página -->
    <h1 style="font-size: 20px; font-weight: 600; color: #1f2937; margin-bottom: 25px;">
        LISTA DE FORNECEDORES
    </h1>

    <!-- SEÇÃO: FILTROS DA PESQUISA -->
    <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
            <i class="fas fa-filter" style="color: #6b7280;"></i>
            <h2 style="font-size: 14px; font-weight: 600; color: #374151; text-transform: uppercase; margin: 0;">
                FILTROS DA PESQUISA
            </h2>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                Parte do nome:
            </label>
            <input type="text" id="filtro_nome" placeholder="Digite parte do nome do fornecedor" style="width: 100%; max-width: 500px; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
        </div>

        <!-- Botões de Ação -->
        <div style="display: flex; gap: 12px;">
            <button type="button" id="btn-adicionar" style="background: #9ca3af; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-plus"></i>
                ADICIONAR
            </button>
            <button type="button" id="btn-consultar" style="background: #9ca3af; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-search"></i>
                CONSULTAR
            </button>
            <button type="button" id="btn-importar" style="background: #9ca3af; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-upload"></i>
                IMPORTAR
            </button>
        </div>
    </div>

    <!-- TABELA DE FORNECEDORES -->
    <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">NOME</th>
                    <th style="padding: 12px 20px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; width: 180px;">CNPJ</th>
                    <th style="padding: 12px 20px; text-align: right; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; width: 130px;">CADASTRO</th>
                    <th style="padding: 12px 20px; text-align: center; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; width: 100px;">AÇÕES</th>
                </tr>
            </thead>
            <tbody id="tabela-fornecedores">
                @forelse($fornecedores as $fornecedor)
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 16px 20px;">
                        <span style="color: #3b82f6; font-size: 13px; font-weight: 500;">{{ strtoupper($fornecedor->razao_social) }}</span>
                        @if($fornecedor->nome_fantasia)
                        <br>
                        <span style="font-size: 11px; color: #6b7280;">({{ $fornecedor->nome_fantasia }})</span>
                        @endif
                    </td>
                    <td style="padding: 16px 20px; text-align: right; font-size: 13px; color: #374151;">
                        {{ $fornecedor->numero_documento_formatado }}
                    </td>
                    <td style="padding: 16px 20px; text-align: right; font-size: 13px; color: #374151;">
                        {{ $fornecedor->created_at->format('d/m/Y') }}
                    </td>
                    <td style="padding: 16px 20px; text-align: center;">
                        <button type="button" class="btn-editar" data-id="{{ $fornecedor->id }}" style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-right: 5px;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn-excluir" data-id="{{ $fornecedor->id }}" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="padding: 40px 20px; text-align: center; color: #6b7280; font-size: 13px;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px; display: block;"></i>
                        Nenhum fornecedor cadastrado ainda.
                        <br>
                        Clique em <strong>ADICIONAR</strong> para cadastrar o primeiro fornecedor.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Paginação -->
        <div style="padding: 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: center; align-items: center; gap: 5px;">
            <button style="padding: 6px 12px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 12px; color: #374151;">&lt;</button>
            <button style="padding: 6px 12px; border: 1px solid #3b82f6; background: #3b82f6; color: white; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">1</button>
            <button style="padding: 6px 12px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 12px; color: #374151;">2</button>
            <button style="padding: 6px 12px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 12px; color: #374151;">3</button>
            <button style="padding: 6px 12px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 12px; color: #374151;">4</button>
            <button style="padding: 6px 12px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 12px; color: #374151;">5</button>
            <span style="padding: 6px 12px; font-size: 12px; color: #6b7280;">...</span>
            <button style="padding: 6px 12px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 12px; color: #374151;">&gt;</button>
        </div>
    </div>
</div>

<!-- Modal: Adicionar Fornecedor -->
<div id="modal-adicionar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto;">
    <div style="min-height: 100%; display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: white; width: 100%; max-width: 700px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-height: 90vh; display: flex; flex-direction: column;">

            <!-- Header do Modal -->
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <h2 id="titulo-modal-fornecedor" style="margin: 0; font-size: 16px; font-weight: 600;">CADASTRO DE FORNECEDORES</h2>
                <button type="button" id="btn-fechar-modal" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px;">&times;</button>
            </div>

            <!-- Conteúdo do Modal (scrollável) -->
            <div style="padding: 25px; overflow-y: auto; flex: 1;">

                <!-- SEÇÃO 1: DADOS DO FORNECEDOR -->
                <div style="margin-bottom: 30px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #3b82f6; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <span style="background: #3b82f6; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">1</span>
                        DADOS DO FORNECEDOR
                    </h3>

                    <!-- Razão social -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">
                            Razão social<span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="razao_social" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>

                    <!-- Nome fantasia -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">Nome fantasia</label>
                        <input type="text" id="nome_fantasia" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>

                    <!-- CNPJ ou CPF -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">
                            CNPJ ou CPF<span style="color: #ef4444;">*</span>
                        </label>
                        <div style="display: grid; grid-template-columns: 100px 1fr 1fr; gap: 10px;">
                            <select id="tipo_documento" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                                <option value="CNPJ">CNPJ</option>
                                <option value="CPF">CPF</option>
                            </select>
                            <input type="text" id="numero_documento" placeholder="00.000.000/0000-00" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                            <input type="text" id="insc_municipal" placeholder="Insc. Municipal" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        </div>
                    </div>

                    <!-- Insc. Estadual -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">Insc. Estadual</label>
                        <input type="text" id="insc_estadual" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>

                    <!-- Ramo de empresa -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">Ramo de empresa</label>
                        <select id="ramo_empresa" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                            <option value="">Selecione...</option>
                            <option value="comercio">Comércio</option>
                            <option value="industria">Indústria</option>
                            <option value="servicos">Serviços</option>
                        </select>
                    </div>

                    <!-- Ramo de atividade -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">Ramo de atividade</label>
                        <input type="text" id="ramo_atividade" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>

                    <!-- CNAE -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">CNAE</label>
                        <input type="text" id="cnae" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>

                    <!-- Representante -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">Representante</label>
                        <input type="text" id="representante" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>

                    <!-- Doc. de identificação -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">Doc. de identificação</label>
                        <div style="display: grid; grid-template-columns: 100px 1fr; gap: 10px;">
                            <select id="tipo_doc_identificacao" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                                <option value="CPF">CPF</option>
                                <option value="RG">RG</option>
                            </select>
                            <input type="text" id="doc_identificacao" placeholder="000.000.000-00" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        </div>
                    </div>

                    <!-- Dados de informação -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">Dados de informação</label>
                        <input type="text" id="dados_informacao" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>
                </div>

                <!-- SEÇÃO 2: CONTATOS -->
                <div style="margin-bottom: 30px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #3b82f6; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <span style="background: #3b82f6; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">2</span>
                        CONTATOS
                    </h3>

                    <!-- Telefone -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">Telefone</label>
                        <input type="text" id="telefone" placeholder="(00) 0000-0000" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>

                    <!-- Celular -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">Celular</label>
                        <input type="text" id="celular" placeholder="(00) 0 0000-0000" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>

                    <!-- E-mail -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">E-mail</label>
                        <input type="email" id="email" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>

                    <!-- Site -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">Site</label>
                        <input type="url" id="site" placeholder="https://" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>
                </div>

                <!-- SEÇÃO 3: ENDEREÇO -->
                <div style="margin-bottom: 30px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #3b82f6; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <span style="background: #3b82f6; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">3</span>
                        ENDEREÇO
                    </h3>

                    <!-- CEP com busca -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">
                            CEP<span style="color: #ef4444;">*</span>
                        </label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="cep" placeholder="00.000-000" maxlength="10" style="flex: 1; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                            <button type="button" id="btn-buscar-cep" style="background: #3b82f6; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Logradouro e Número -->
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">
                                Logradouro<span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" id="logradouro" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">
                                Número<span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" id="numero" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        </div>
                    </div>

                    <!-- Cidade e UF -->
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">
                                Cidade<span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" id="cidade" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">
                                UF<span style="color: #ef4444;">*</span>
                            </label>
                            <select id="uf" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px; background: white;">
                                <option value="">Selecione...</option>
                                <option value="AC">AC - Acre</option>
                                <option value="AL">AL - Alagoas</option>
                                <option value="AP">AP - Amapá</option>
                                <option value="AM">AM - Amazonas</option>
                                <option value="BA">BA - Bahia</option>
                                <option value="CE">CE - Ceará</option>
                                <option value="DF">DF - Distrito Federal</option>
                                <option value="ES">ES - Espírito Santo</option>
                                <option value="GO">GO - Goiás</option>
                                <option value="MA">MA - Maranhão</option>
                                <option value="MT">MT - Mato Grosso</option>
                                <option value="MS">MS - Mato Grosso do Sul</option>
                                <option value="MG">MG - Minas Gerais</option>
                                <option value="PA">PA - Pará</option>
                                <option value="PB">PB - Paraíba</option>
                                <option value="PR">PR - Paraná</option>
                                <option value="PE">PE - Pernambuco</option>
                                <option value="PI">PI - Piauí</option>
                                <option value="RJ">RJ - Rio de Janeiro</option>
                                <option value="RN">RN - Rio Grande do Norte</option>
                                <option value="RS">RS - Rio Grande do Sul</option>
                                <option value="RO">RO - Rondônia</option>
                                <option value="RR">RR - Roraima</option>
                                <option value="SC">SC - Santa Catarina</option>
                                <option value="SP">SP - São Paulo</option>
                                <option value="SE">SE - Sergipe</option>
                                <option value="TO">TO - Tocantins</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bairro -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">
                            Bairro<span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="bairro" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>

                    <!-- Complemento -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; color: #374151; font-weight: 500; margin-bottom: 5px;">Complemento</label>
                        <input type="text" id="complemento" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                    </div>
                </div>

                <!-- SEÇÃO 4: LINHAS DE FORNECIMENTO -->
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #3b82f6; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <span style="background: #3b82f6; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">4</span>
                        LINHAS DE FORNECIMENTO
                    </h3>

                    <button type="button" id="btn-adicionar-linha" style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; margin-bottom: 10px;">
                        ADICIONAR A LINHA DE LINHAS DE FORNECIMENTO
                    </button>

                    <div id="linhas-fornecimento" style="padding: 15px; background: #f9fafb; border-radius: 4px; font-size: 12px; color: #6b7280; text-align: center;">
                        NENHUMA - ISENÇÃO
                    </div>
                </div>

            </div>

            <!-- Footer do Modal -->
            <div style="padding: 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" id="btn-cancelar-fornecedor" style="background: #e5e7eb; color: #374151; border: none; padding: 10px 24px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    <i class="fas fa-times"></i> CANCELAR
                </button>
                <button type="button" id="btn-salvar-fornecedor" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 10px 24px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    <i class="fas fa-save"></i> SALVAR
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Importar Fornecedores -->
<div id="modal-importar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto;">
    <div style="min-height: 100%; display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: white; width: 100%; max-width: 800px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-height: 90vh; display: flex; flex-direction: column;">

            <!-- Header do Modal -->
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 16px; font-weight: 600;">IMPORTAR FORNECEDORES</h2>
                <button type="button" id="btn-fechar-modal-importar" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px;">&times;</button>
            </div>

            <!-- Conteúdo do Modal (scrollável) -->
            <div style="padding: 25px; overflow-y: auto; flex: 1;">

                <!-- SEÇÃO 1: PLANILHA PARA IMPORTAÇÃO -->
                <div style="margin-bottom: 30px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #3b82f6; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                        <span style="background: #3b82f6; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">1</span>
                        PLANILHA PARA IMPORTAÇÃO
                    </h3>

                    <!-- Aviso Importante -->
                    <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; margin-bottom: 15px; border-radius: 4px;">
                        <p style="font-size: 12px; color: #1e40af; margin: 0; line-height: 1.6;">
                            <strong>✨ SISTEMA INTELIGENTE:</strong> Nosso sistema aceita <strong>QUALQUER formato de planilha Excel/CSV</strong>!
                            Não é necessário usar o modelo abaixo. O sistema detecta automaticamente as colunas de:
                            <strong>Razão Social, Nome Fantasia, CNPJ/CPF, Inscrição Estadual, Inscrição Municipal, Porte e CNAE</strong>.
                        </p>
                    </div>

                    <p style="font-size: 12px; color: #6b7280; margin-bottom: 15px;">
                        Abaixo está uma planilha modelo (opcional) que você pode baixar e preencher com os dados de fornecedores:
                    </p>

                    <!-- Tabela Exemplo -->
                    <div style="overflow-x: auto; margin-bottom: 15px; border: 1px solid #e5e7eb; border-radius: 4px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                            <thead>
                                <tr style="background: #3b82f6; color: white;">
                                    <th style="padding: 8px; text-align: left; border-right: 1px solid rgba(255,255,255,0.2);">#</th>
                                    <th style="padding: 8px; text-align: left; border-right: 1px solid rgba(255,255,255,0.2);">RAZÃO SOCIAL</th>
                                    <th style="padding: 8px; text-align: left; border-right: 1px solid rgba(255,255,255,0.2);">NOME FANTASIA</th>
                                    <th style="padding: 8px; text-align: left; border-right: 1px solid rgba(255,255,255,0.2);">CPF/CNPJ</th>
                                    <th style="padding: 8px; text-align: left; border-right: 1px solid rgba(255,255,255,0.2);">INSCRIÇÃO ESTADUAL</th>
                                    <th style="padding: 8px; text-align: left; border-right: 1px solid rgba(255,255,255,0.2);">INSCRIÇÃO MUNICIPAL</th>
                                    <th style="padding: 8px; text-align: left; border-right: 1px solid rgba(255,255,255,0.2);">PORTE DA EMPRESA</th>
                                    <th style="padding: 8px; text-align: left;">CNAE</th>
                                </tr>
                            </thead>
                            <tbody style="background: white;">
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 8px; color: #6b7280;">1</td>
                                    <td style="padding: 8px; color: #374151;">Exemplo LTDA</td>
                                    <td style="padding: 8px; color: #374151;">Exemplo</td>
                                    <td style="padding: 8px; color: #374151;">00.000.000/0000-00</td>
                                    <td style="padding: 8px; color: #374151;">000000000</td>
                                    <td style="padding: 8px; color: #374151;">0000000</td>
                                    <td style="padding: 8px; color: #374151;">EPP</td>
                                    <td style="padding: 8px; color: #374151;">0000-0/00</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 8px; color: #6b7280;">2</td>
                                    <td style="padding: 8px; color: #374151;">...</td>
                                    <td style="padding: 8px; color: #374151;">...</td>
                                    <td style="padding: 8px; color: #374151;">...</td>
                                    <td style="padding: 8px; color: #374151;">...</td>
                                    <td style="padding: 8px; color: #374151;">...</td>
                                    <td style="padding: 8px; color: #374151;">...</td>
                                    <td style="padding: 8px; color: #374151;">...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" id="btn-download-modelo" style="background: #f59e0b; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-download"></i> DOWNLOAD DA PLANILHA MODELO (OPCIONAL)
                    </button>
                </div>

                <!-- SEÇÃO 2: ENVIAR PLANILHA -->
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #3b82f6; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <span style="background: #3b82f6; color: white; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">2</span>
                        ENVIAR PLANILHA
                    </h3>

                    <!-- Área de Upload -->
                    <div id="upload-area" style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 40px; text-align: center; background: #f9fafb; cursor: pointer; transition: all 0.2s;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #9ca3af; margin-bottom: 15px;"></i>
                        <p style="font-size: 14px; color: #374151; font-weight: 500; margin: 0 0 5px 0;">Clique ou arraste o arquivo aqui</p>
                        <p style="font-size: 12px; color: #6b7280; margin: 0;">Formatos aceitos: .xlsx, .xls, .csv (máx 10MB)</p>
                        <input type="file" id="arquivo-importacao" accept=".xlsx,.xls,.csv" style="display: none;">
                    </div>

                    <!-- Arquivo Selecionado -->
                    <div id="arquivo-selecionado" style="display: none; margin-top: 15px; padding: 12px; background: #eff6ff; border-radius: 4px; border-left: 4px solid #3b82f6;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-file-excel" style="font-size: 24px; color: #10b981;"></i>
                                <div>
                                    <p id="nome-arquivo" style="margin: 0; font-size: 13px; font-weight: 600; color: #374151;"></p>
                                    <p id="tamanho-arquivo" style="margin: 0; font-size: 11px; color: #6b7280;"></p>
                                </div>
                            </div>
                            <button type="button" id="btn-remover-arquivo" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 11px;">
                                <i class="fas fa-times"></i> Remover
                            </button>
                        </div>
                    </div>

                    <!-- Progresso da importação -->
                    <div id="progresso-importacao" style="display: none; margin-top: 15px;">
                        <div style="background: #f3f4f6; border-radius: 8px; padding: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-size: 12px; font-weight: 600; color: #374151;">Importando fornecedores...</span>
                                <span id="progresso-texto" style="font-size: 12px; color: #6b7280;">0%</span>
                            </div>
                            <div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
                                <div id="barra-progresso" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); height: 100%; width: 0%; transition: width 0.3s;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Resultado da importação -->
                    <div id="resultado-importacao" style="display: none; margin-top: 15px;"></div>
                </div>

            </div>

            <!-- Footer do Modal -->
            <div style="padding: 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" id="btn-cancelar-importar" style="background: #6b7280; color: white; border: none; padding: 10px 24px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    CANCELAR
                </button>
                <button type="button" id="btn-importar-salvar" disabled style="background: #3b82f6; color: white; border: none; padding: 10px 24px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; opacity: 0.5;">
                    <i class="fas fa-upload"></i> IMPORTAR
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Excluir Fornecedor -->
<div id="modal-excluir" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; width: 100%; max-width: 450px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); margin: 20px;">

        <!-- Ícone de Aviso -->
        <div style="text-align: center; padding: 30px 30px 20px 30px;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; background: #fee2e2; border-radius: 50%; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: #dc2626;"></i>
            </div>

            <h2 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 600; color: #1f2937;">
                Confirmar Exclusão
            </h2>

            <p style="margin: 0 0 10px 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                Tem certeza que deseja excluir o fornecedor:
            </p>

            <p id="nome-fornecedor-excluir" style="margin: 0; font-size: 15px; font-weight: 600; color: #dc2626;">
                <!-- Nome será inserido aqui -->
            </p>
        </div>

        <!-- Aviso -->
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 0 30px 20px 30px; border-radius: 4px;">
            <p style="margin: 0; font-size: 12px; color: #92400e; line-height: 1.5;">
                <i class="fas fa-info-circle"></i> <strong>Atenção:</strong> Esta ação não poderá ser desfeita. O fornecedor será removido permanentemente do sistema.
            </p>
        </div>

        <!-- Botões -->
        <div style="padding: 0 30px 30px 30px; display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" id="btn-cancelar-excluir" style="background: #e5e7eb; color: #374151; border: none; padding: 12px 24px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                <i class="fas fa-times"></i> CANCELAR
            </button>
            <button type="button" id="btn-confirmar-excluir" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; border: none; padding: 12px 24px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                <i class="fas fa-trash"></i> EXCLUIR
            </button>
        </div>

    </div>
</div>

<!-- Modal: Consultar Fornecedor -->
<div id="modal-consultar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto;">
    <div style="min-height: 100%; display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: white; width: 100%; max-width: 900px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-height: 90vh; display: flex; flex-direction: column;">

            <!-- Header -->
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 16px; font-weight: 600;"><i class="fas fa-search"></i> CONSULTAR FORNECEDOR</h2>
                <button type="button" id="btn-fechar-modal-consultar" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px;">&times;</button>
            </div>

            <!-- Conteúdo -->
            <div style="padding: 25px; overflow-y: auto; flex: 1;">

                <!-- Campo de Busca -->
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; color: #374151; font-weight: 500; margin-bottom: 8px;">
                        Buscar por CNPJ/CPF ou Razão Social
                    </label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="input-busca-fornecedor" placeholder="Digite CNPJ, CPF ou Razão Social..."
                            style="flex: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        <button type="button" id="btn-buscar-fornecedor" style="background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-search"></i> BUSCAR
                        </button>
                    </div>
                </div>

                <!-- Resultados -->
                <div id="resultados-consulta-fornecedor" style="display: none;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 10px;">Resultados da Busca</h3>
                    <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 4px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <thead>
                                <tr style="background: #f9fafb;">
                                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #e5e7eb; font-weight: 600;">CNPJ/CPF</th>
                                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #e5e7eb; font-weight: 600;">RAZÃO SOCIAL</th>
                                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #e5e7eb; font-weight: 600;">CIDADE</th>
                                    <th style="padding: 10px; text-align: center; border-bottom: 2px solid #e5e7eb; font-weight: 600;">AÇÕES</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-resultados-consulta">
                                <!-- Resultados via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <p id="msg-sem-resultados" style="display: none; text-align: center; padding: 30px; color: #6b7280; font-size: 13px;">
                        <i class="fas fa-info-circle"></i> Nenhum fornecedor encontrado com este critério.
                    </p>
                </div>

                <!-- Mensagem inicial -->
                <div id="msg-inicial-consulta" style="text-align: center; padding: 40px; color: #9ca3af;">
                    <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p style="margin: 0; font-size: 13px;">Digite um CNPJ, CPF ou Razão Social e clique em BUSCAR para encontrar fornecedores.</p>
                </div>

            </div>

            <!-- Footer -->
            <div style="padding: 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end;">
                <button type="button" id="btn-fechar-consultar" style="background: #e5e7eb; color: #374151; border: none; padding: 10px 24px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-times"></i> FECHAR
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Visualizar Detalhes do Fornecedor -->
<div id="modal-visualizar-fornecedor" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto;">
    <div style="min-height: 100%; display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: white; width: 100%; max-width: 900px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-height: 90vh; display: flex; flex-direction: column;">

            <!-- Header -->
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 16px; font-weight: 600;"><i class="fas fa-eye"></i> DETALHES DO FORNECEDOR</h2>
                <button type="button" id="btn-fechar-modal-visualizar" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px;">&times;</button>
            </div>

            <!-- Conteúdo -->
            <div style="padding: 25px; overflow-y: auto; flex: 1;">

                <!-- Seção 1: Informações Básicas -->
                <div style="background: #f9fafb; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-building" style="color: #10b981;"></i> INFORMAÇÕES BÁSICAS
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">Razão Social</label>
                            <p id="visualizar-razao-social" style="margin: 0; font-size: 13px; color: #374151; font-weight: 500;">-</p>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">Nome Fantasia</label>
                            <p id="visualizar-nome-fantasia" style="margin: 0; font-size: 13px; color: #374151;">-</p>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">CNPJ/CPF</label>
                            <p id="visualizar-cnpj" style="margin: 0; font-size: 13px; color: #374151; font-family: 'Courier New', monospace;">-</p>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">Porte da Empresa</label>
                            <p id="visualizar-porte" style="margin: 0; font-size: 13px; color: #374151;">-</p>
                        </div>
                    </div>
                </div>

                <!-- Seção 2: Contato -->
                <div style="background: #f9fafb; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-phone" style="color: #10b981;"></i> CONTATO
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">E-mail</label>
                            <p id="visualizar-email" style="margin: 0; font-size: 13px; color: #374151;">-</p>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">Telefone</label>
                            <p id="visualizar-telefone" style="margin: 0; font-size: 13px; color: #374151;">-</p>
                        </div>
                    </div>
                </div>

                <!-- Seção 3: Endereço -->
                <div style="background: #f9fafb; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-map-marker-alt" style="color: #10b981;"></i> ENDEREÇO
                    </h3>
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">Logradouro</label>
                            <p id="visualizar-logradouro" style="margin: 0; font-size: 13px; color: #374151;">-</p>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">Número</label>
                            <p id="visualizar-numero" style="margin: 0; font-size: 13px; color: #374151;">-</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">Complemento</label>
                            <p id="visualizar-complemento" style="margin: 0; font-size: 13px; color: #374151;">-</p>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">Bairro</label>
                            <p id="visualizar-bairro" style="margin: 0; font-size: 13px; color: #374151;">-</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">Cidade</label>
                            <p id="visualizar-cidade" style="margin: 0; font-size: 13px; color: #374151;">-</p>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">UF</label>
                            <p id="visualizar-uf" style="margin: 0; font-size: 13px; color: #374151;">-</p>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">CEP</label>
                            <p id="visualizar-cep" style="margin: 0; font-size: 13px; color: #374151; font-family: 'Courier New', monospace;">-</p>
                        </div>
                    </div>
                </div>

                <!-- Seção 4: Itens Fornecidos -->
                <div style="background: #f9fafb; padding: 20px; border-radius: 6px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-boxes" style="color: #10b981;"></i> ITENS FORNECIDOS
                        <span id="visualizar-total-itens" style="background: #10b981; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">0</span>
                    </h3>
                    <div id="visualizar-lista-itens" style="max-height: 200px; overflow-y: auto;">
                        <p style="color: #6b7280; font-size: 13px; text-align: center; padding: 20px;">Nenhum item cadastrado</p>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div style="padding: 20px 25px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" id="btn-editar-do-visualizar" style="background: #f59e0b; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-edit"></i> EDITAR
                </button>
                <button type="button" id="btn-fechar-modal-visualizar-footer" style="background: #9ca3af; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-times"></i> FECHAR
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modal de Notificação (substituir alert) -->
<div id="modal-notificacao" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 99999; overflow-y: auto;">
    <div style="min-height: 100%; display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div id="notificacao-container" style="background: white; width: 100%; max-width: 600px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <!-- Header -->
            <div id="notificacao-header" style="padding: 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <h3 id="notificacao-titulo" style="margin: 0; font-size: 16px; font-weight: 600; color: white;"></h3>
                <button type="button" onclick="fecharNotificacao()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <!-- Conteúdo -->
            <div style="padding: 25px;">
                <p id="notificacao-mensagem" style="margin: 0 0 15px 0; font-size: 14px; color: #374151; white-space: pre-wrap; word-break: break-word; user-select: text; cursor: text;"></p>
                <div id="notificacao-detalhes" style="display: none; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 15px; margin-top: 15px;">
                    <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 600; color: #374151;">Detalhes técnicos:</p>
                    <pre id="notificacao-detalhes-texto" style="margin: 0; font-size: 11px; color: #6b7280; white-space: pre-wrap; word-break: break-all; font-family: monospace; user-select: text; cursor: text;"></pre>
                </div>
            </div>
            <!-- Footer -->
            <div style="padding: 0 25px 25px 25px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" id="btn-copiar-erro" onclick="copiarErro()" style="display: none; background: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-copy"></i> COPIAR
                </button>
                <button type="button" onclick="fecharNotificacao()" style="background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 PÁGINA FORNECEDORES CARREGADA - DOMContentLoaded disparado!');
    console.log('⏰ Timestamp:', new Date().toISOString());

    // Variáveis globais para controle de modo
    let modoEdicao = false;
    let fornecedorIdEditando = null;
    let fornecedorIdExcluindo = null;
    let ultimoErro = '';

    console.log('✅ Variáveis globais inicializadas:', { modoEdicao, fornecedorIdEditando, fornecedorIdExcluindo });

    // Sistema de notificação copiável
    window.mostrarNotificacao = function(titulo, mensagem, tipo = 'info', detalhes = null) {
        const modal = document.getElementById('modal-notificacao');
        const header = document.getElementById('notificacao-header');
        const tituloEl = document.getElementById('notificacao-titulo');
        const mensagemEl = document.getElementById('notificacao-mensagem');
        const detalhesDiv = document.getElementById('notificacao-detalhes');
        const detalhesTexto = document.getElementById('notificacao-detalhes-texto');
        const btnCopiar = document.getElementById('btn-copiar-erro');

        // Definir cores por tipo
        const cores = {
            'success': { bg: '#10b981', icon: 'fa-check-circle' },
            'error': { bg: '#ef4444', icon: 'fa-exclamation-circle' },
            'warning': { bg: '#f59e0b', icon: 'fa-exclamation-triangle' },
            'info': { bg: '#3b82f6', icon: 'fa-info-circle' }
        };

        const config = cores[tipo] || cores.info;

        // Aplicar estilo
        header.style.background = config.bg;
        tituloEl.innerHTML = `<i class="fas ${config.icon}"></i> ${titulo}`;
        mensagemEl.textContent = mensagem;

        // Detalhes técnicos (se houver)
        if (detalhes) {
            detalhesTexto.textContent = detalhes;
            detalhesDiv.style.display = 'block';
            btnCopiar.style.display = 'inline-block';
            ultimoErro = `${titulo}\n\n${mensagem}\n\nDetalhes técnicos:\n${detalhes}`;
        } else {
            detalhesDiv.style.display = 'none';
            btnCopiar.style.display = 'none';
            ultimoErro = `${titulo}\n\n${mensagem}`;
        }

        modal.style.display = 'block';
    };

    window.fecharNotificacao = function() {
        document.getElementById('modal-notificacao').style.display = 'none';
    };

    window.copiarErro = function() {
        navigator.clipboard.writeText(ultimoErro).then(() => {
            document.getElementById('btn-copiar-erro').innerHTML = '<i class="fas fa-check"></i> COPIADO!';
            setTimeout(() => {
                document.getElementById('btn-copiar-erro').innerHTML = '<i class="fas fa-copy"></i> COPIAR';
            }, 2000);
        }).catch(err => {
            console.error('Erro ao copiar:', err);
        });
    };

    // Botão Adicionar - Abrir modal em modo cadastro
    document.getElementById('btn-adicionar').addEventListener('click', function() {
        modoEdicao = false;
        fornecedorIdEditando = null;
        document.getElementById('titulo-modal-fornecedor').textContent = 'CADASTRO DE FORNECEDORES';
        limparFormularioFornecedor();
        document.getElementById('modal-adicionar').style.display = 'block';
    });

    // Fechar modal
    document.getElementById('btn-fechar-modal').addEventListener('click', function() {
        document.getElementById('modal-adicionar').style.display = 'none';
    });

    document.getElementById('btn-cancelar-fornecedor').addEventListener('click', function() {
        document.getElementById('modal-adicionar').style.display = 'none';
    });

    // TESTE: Verificar se o botão existe
    const btnSalvarFornecedor = document.getElementById('btn-salvar-fornecedor');
    console.log('🔍 BOTÃO SALVAR FORNECEDOR:', btnSalvarFornecedor);

    if (!btnSalvarFornecedor) {
        console.error('❌ ERRO: Botão SALVAR não encontrado no DOM!');
        alert('ERRO CRÍTICO: Botão SALVAR não encontrado. Recarregue a página.');
    } else {
        console.log('✅ Botão SALVAR encontrado! Adicionando event listener...');
    }

    btnSalvarFornecedor.addEventListener('click', async function(e) {
        console.log('🖱️ CLIQUE DETECTADO NO BOTÃO SALVAR!');
        console.log('Event:', e);

        // Validar campos obrigatórios
        const razaoSocial = document.getElementById('razao_social').value;
        const numeroDoc = document.getElementById('numero_documento').value;
        const cep = document.getElementById('cep').value;
        const logradouro = document.getElementById('logradouro').value;
        const numero = document.getElementById('numero').value;
        const cidade = document.getElementById('cidade').value;
        const uf = document.getElementById('uf').value;
        const bairro = document.getElementById('bairro').value;

        // Debug: Mostrar valores dos campos
        console.log('🔍 VALIDANDO CAMPOS:', {
            razaoSocial, numeroDoc, cep, logradouro, numero, cidade, uf, bairro
        });

        // Validar cada campo individualmente e mostrar qual está faltando
        const camposObrigatorios = [
            { nome: 'Razão Social', valor: razaoSocial, id: 'razao_social' },
            { nome: 'CNPJ/CPF', valor: numeroDoc, id: 'numero_documento' },
            { nome: 'CEP', valor: cep, id: 'cep' },
            { nome: 'Logradouro', valor: logradouro, id: 'logradouro' },
            { nome: 'Número', valor: numero, id: 'numero' },
            { nome: 'Cidade', valor: cidade, id: 'cidade' },
            { nome: 'UF', valor: uf, id: 'uf' },
            { nome: 'Bairro', valor: bairro, id: 'bairro' }
        ];

        const camposFaltando = camposObrigatorios.filter(c => !c.valor || c.valor.trim() === '');

        if (camposFaltando.length > 0) {
            const listaFaltando = camposFaltando.map(c => `• ${c.nome}`).join('\n');

            alert(`⚠️ CAMPOS OBRIGATÓRIOS FALTANDO:\n\n${listaFaltando}\n\nPor favor, preencha todos os campos marcados com * (asterisco).`);

            // Focar no primeiro campo faltando
            document.getElementById(camposFaltando[0].id).focus();
            document.getElementById(camposFaltando[0].id).style.borderColor = '#ef4444';

            console.error('❌ CAMPOS FALTANDO:', camposFaltando);
            return;
        }

        // Validar UF (deve ter 2 letras)
        if (uf.length !== 2 || uf.match(/[^A-Z]/i)) {
            alert('⚠️ UF INVÁLIDO!\n\nO campo UF deve conter exatamente 2 letras.\nExemplos: MG, SP, RJ, etc.\n\nPor favor, selecione um estado válido da lista.');
            document.getElementById('uf').focus();
            document.getElementById('uf').style.borderColor = '#ef4444';
            console.error('❌ UF INVÁLIDO:', uf);
            return;
        }

        console.log('✅ TODOS OS CAMPOS VALIDADOS COM SUCESSO!');

        // Coletar todos os dados do formulário
        const dados = {
            tipo_documento: document.getElementById('tipo_documento').value,
            numero_documento: numeroDoc,
            razao_social: razaoSocial,
            nome_fantasia: document.getElementById('nome_fantasia').value || null,
            inscricao_estadual: document.getElementById('insc_estadual')?.value || null,
            inscricao_municipal: document.getElementById('insc_municipal')?.value || null,
            telefone: document.getElementById('telefone').value || null,
            celular: document.getElementById('celular').value || null,
            email: document.getElementById('email').value || null,
            site: document.getElementById('site').value || null,
            cep: cep,
            logradouro: logradouro,
            numero: numero,
            complemento: document.getElementById('complemento').value || null,
            bairro: bairro,
            cidade: cidade,
            uf: uf,
            observacoes: null
        };

        // Coletar itens de fornecimento
        const itens = [];
        const linhasFornecimento = document.querySelectorAll('.linha-fornecimento');
        linhasFornecimento.forEach(linha => {
            const descricao = linha.querySelector('.item-descricao').value;
            const catmat = linha.querySelector('.item-catmat').value;
            const unidade = linha.querySelector('.item-unidade').value;
            const preco = linha.querySelector('.item-preco').value;

            if (descricao && unidade) {  // Apenas itens válidos
                itens.push({
                    descricao: descricao,
                    codigo_catmat: catmat || null,
                    unidade: unidade,
                    preco_referencia: preco ? parseFloat(preco) : null
                });
            }
        });

        // Adicionar itens aos dados
        dados.itens = itens;

        // Debug: Mostrar dados que serão enviados
        console.log('📦 DADOS A SEREM ENVIADOS:', dados);
        console.log('📊 TOTAL DE ITENS:', dados.itens.length);

        // Desabilitar botão durante salvamento
        const btnSalvar = this;
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SALVANDO...';

        try {
            // Verificar modo (cadastro ou edição)
            const url = modoEdicao ? `fornecedores/${fornecedorIdEditando}` : 'fornecedores';
            const method = modoEdicao ? 'PUT' : 'POST';

            console.log('🌐 ENVIANDO REQUISIÇÃO:', { url, method });

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(dados)
            });

            console.log('📡 RESPOSTA RECEBIDA:', {
                status: response.status,
                statusText: response.statusText,
                ok: response.ok
            });

            // Tentar fazer parse do JSON
            let result;
            const contentType = response.headers.get('content-type');

            if (contentType && contentType.includes('application/json')) {
                result = await response.json();
            } else {
                // Se não for JSON, capturar o texto bruto (ex: erro HTML)
                const textResponse = await response.text();

                let detalhes = `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`;
                detalhes += `🔴 ERRO ${response.status}\n`;
                detalhes += `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n`;
                detalhes += `📍 REQUISIÇÃO:\n`;
                detalhes += `   URL: ${url}\n`;
                detalhes += `   Método: ${method}\n`;
                detalhes += `   Status HTTP: ${response.status}\n`;
                detalhes += `   Content-Type: ${contentType || 'não especificado'}\n\n`;
                detalhes += `📦 RESPOSTA DO SERVIDOR (texto bruto):\n`;
                detalhes += textResponse.substring(0, 5000); // Limitar a 5000 caracteres

                mostrarNotificacao(
                    'Erro no Servidor',
                    'O servidor retornou uma resposta inesperada (não-JSON). Veja os detalhes técnicos.',
                    'error',
                    detalhes
                );

                // Reabilitar botão
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = '<i class="fas fa-save"></i> SALVAR';
                return;
            }

            // Debug: Log da resposta completa
            console.log('📡 Resposta do servidor:', result);
            console.log('✅ Success:', result.success);
            console.log('📦 Fornecedor salvo:', result.fornecedor);

            if (result.success) {
                // Exibir mensagem de sucesso
                mostrarNotificacao(result.message || 'Fornecedor cadastrado com sucesso!', 'success');

                console.log('🔄 Fechando modal e recarregando página em 1 segundo...');
                document.getElementById('modal-adicionar').style.display = 'none';

                // Aguardar 1 segundo antes de recarregar para o usuário ver a mensagem
                setTimeout(() => {
                    console.log('🔄 Executando reload...');
                    location.reload(true); // Recarregar forçando cache
                }, 1000);
            } else {
                // Montar detalhes técnicos do erro
                let detalhes = `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`;
                detalhes += `🔴 ERRO ${response.status}\n`;
                detalhes += `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n`;

                detalhes += `📍 REQUISIÇÃO:\n`;
                detalhes += `   URL: ${url}\n`;
                detalhes += `   Método: ${method}\n`;
                detalhes += `   Status HTTP: ${response.status}\n\n`;

                // Se o servidor retornou error_details com arquivo e linha
                if (result.error_details) {
                    detalhes += `🐛 DETALHES DO ERRO:\n`;
                    detalhes += `   Arquivo: ${result.error_details.file}\n`;
                    detalhes += `   Linha: ${result.error_details.line}\n`;
                    detalhes += `   Mensagem: ${result.error_details.message}\n\n`;

                    if (result.error_details.trace && Array.isArray(result.error_details.trace)) {
                        detalhes += `📚 STACK TRACE:\n`;
                        result.error_details.trace.forEach((line, index) => {
                            if (line.trim()) {
                                detalhes += `   ${index + 1}. ${line.trim()}\n`;
                            }
                        });
                    }
                } else {
                    detalhes += `📦 RESPOSTA DO SERVIDOR:\n${JSON.stringify(result, null, 2)}`;
                }

                // Exibir mensagem de erro
                const mensagemErro = result.message || 'Ocorreu um erro ao salvar o fornecedor';
                mostrarNotificacao(mensagemErro, 'error');

                // Se tiver detalhes técnicos, logar no console
                if (detalhes) {
                    console.error('Detalhes do erro ao salvar fornecedor:', detalhes);
                }
            }

        } catch (error) {
            console.error('Erro ao salvar fornecedor:', error);

            // Capturar detalhes técnicos completos
            let detalhes = `URL: ${modoEdicao ? 'fornecedores/' + fornecedorIdEditando : 'fornecedores'}\n`;
            detalhes += `Método: ${modoEdicao ? 'PUT' : 'POST'}\n`;
            detalhes += `Erro: ${error.message}\n\n`;
            if (error.stack) {
                detalhes += `Stack Trace:\n${error.stack}`;
            }

            // Exibir mensagem de erro genérica
            mostrarNotificacao('Erro ao salvar o fornecedor. Verifique o console para detalhes.', 'error');

            // Logar detalhes no console
            console.error('Erro ao salvar fornecedor:', detalhes);
        } finally {
            // Reabilitar botão
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = '<i class="fas fa-save"></i> SALVAR';
        }
    });

    // Buscar CEP usando ViaCEP
    document.getElementById('btn-buscar-cep').addEventListener('click', async function() {
        const cepInput = document.getElementById('cep');
        const cep = cepInput.value.replace(/\D/g, '');

        if (cep.length !== 8) {
            mostrarNotificacao(
                'CEP Inválido',
                'Digite um CEP com 8 dígitos.',
                'warning'
            );
            return;
        }

        // Desabilitar botão durante busca
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> BUSCANDO...';

        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const dados = await response.json();

            if (dados.erro) {
                mostrarNotificacao(
                    'CEP Não Encontrado',
                    'O CEP informado não foi encontrado na base dos Correios.',
                    'warning'
                );
                return;
            }

            // Preencher campos automaticamente
            document.getElementById('logradouro').value = dados.logradouro || '';
            document.getElementById('bairro').value = dados.bairro || '';
            document.getElementById('cidade').value = dados.localidade || '';
            document.getElementById('uf').value = dados.uf || '';
            document.getElementById('complemento').value = dados.complemento || '';

            // Focar no campo número
            document.getElementById('numero').focus();

        } catch (error) {
            console.error('Erro ao buscar CEP:', error);

            let detalhes = `CEP: ${cep}\n`;
            detalhes += `URL: https://viacep.com.br/ws/${cep}/json/\n`;
            detalhes += `Erro: ${error.message}\n\n`;
            if (error.stack) {
                detalhes += `Stack Trace:\n${error.stack}`;
            }

            mostrarNotificacao(
                'Erro ao Buscar CEP',
                'Não foi possível buscar o CEP. Verifique sua conexão e tente novamente.',
                'error',
                detalhes
            );
        } finally {
            // Reabilitar botão
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search"></i> BUSCAR CEP';
        }
    });

    // Contador de linhas de fornecimento
    let contadorLinhas = 0;

    // Adicionar linha de fornecimento
    document.getElementById('btn-adicionar-linha').addEventListener('click', function() {
        contadorLinhas++;
        const container = document.getElementById('linhas-fornecimento');

        // Se é a primeira linha, limpar mensagem "NENHUMA - ISENÇÃO"
        if (contadorLinhas === 1) {
            container.innerHTML = '';
            container.style.textAlign = 'left';
            container.style.padding = '0';
            container.style.background = 'transparent';
        }

        // Criar HTML da nova linha
        const novaLinha = document.createElement('div');
        novaLinha.className = 'linha-fornecimento';
        novaLinha.setAttribute('data-linha-id', contadorLinhas);
        novaLinha.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px; margin-bottom: 10px; padding: 10px; background: white; border: 1px solid #e5e7eb; border-radius: 4px; align-items: end;';

        novaLinha.innerHTML = `
            <div>
                <label style="display: block; font-size: 11px; color: #374151; font-weight: 500; margin-bottom: 5px;">Descrição do Item/Serviço *</label>
                <input type="text" name="item_descricao[]" class="item-descricao" required
                    style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;"
                    placeholder="Ex: Papel A4 sulfite">
            </div>
            <div>
                <label style="display: block; font-size: 11px; color: #374151; font-weight: 500; margin-bottom: 5px;">Código CATMAT</label>
                <input type="text" name="item_catmat[]" class="item-catmat"
                    style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;"
                    placeholder="Ex: 123456">
            </div>
            <div>
                <label style="display: block; font-size: 11px; color: #374151; font-weight: 500; margin-bottom: 5px;">Unidade *</label>
                <input type="text" name="item_unidade[]" class="item-unidade" required
                    style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;"
                    placeholder="Ex: RESMA" value="UNIDADE">
            </div>
            <div>
                <label style="display: block; font-size: 11px; color: #374151; font-weight: 500; margin-bottom: 5px;">Preço Ref. (R$)</label>
                <input type="number" name="item_preco[]" class="item-preco" step="0.01" min="0"
                    style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;"
                    placeholder="0.00">
            </div>
            <div>
                <button type="button" class="btn-remover-linha" data-linha-id="${contadorLinhas}"
                    style="background: #ef4444; color: white; border: none; padding: 8px 12px; border-radius: 4px; font-size: 11px; cursor: pointer; height: 32px;"
                    title="Remover linha">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        container.appendChild(novaLinha);

        // Adicionar evento de remoção
        novaLinha.querySelector('.btn-remover-linha').addEventListener('click', function() {
            const linhaId = this.getAttribute('data-linha-id');
            removerLinhaFornecimento(linhaId);
        });

        // Focar no campo descrição da nova linha
        novaLinha.querySelector('.item-descricao').focus();
    });

    // Função para remover linha de fornecimento
    function removerLinhaFornecimento(linhaId) {
        const linha = document.querySelector(`.linha-fornecimento[data-linha-id="${linhaId}"]`);
        if (linha) {
            linha.remove();

            // Se não sobrou nenhuma linha, mostrar mensagem "NENHUMA - ISENÇÃO"
            const container = document.getElementById('linhas-fornecimento');
            if (container.children.length === 0) {
                container.innerHTML = 'NENHUMA - ISENÇÃO';
                container.style.textAlign = 'center';
                container.style.padding = '15px';
                container.style.background = '#f9fafb';
            }
        }
    }

    // Máscaras de input e consulta automática CNPJ
    const cnpjInput = document.getElementById('numero_documento');
    let timeoutCNPJ = null;

    if (cnpjInput) {
        cnpjInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            const tipo = document.getElementById('tipo_documento').value;

            if (tipo === 'CNPJ' && value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');

                // Se CNPJ completo (14 dígitos), consultar Receita Federal
                if (value.replace(/\D/g, '').length === 14) {
                    // Debounce - aguarda 500ms após parar de digitar
                    clearTimeout(timeoutCNPJ);
                    timeoutCNPJ = setTimeout(() => {
                        consultarCNPJ(value);
                    }, 500);
                }
            } else if (tipo === 'CPF' && value.length <= 11) {
                value = value.replace(/^(\d{3})(\d)/, '$1.$2');
                value = value.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1-$2');
            }

            e.target.value = value;
        });
    }

    // Função para consultar CNPJ na Receita Federal
    function consultarCNPJ(cnpj) {
        // Verificar se estamos em modo edição (não consultar se já tem dados)
        if (modoEdicao) {
            return;
        }

        const cnpjLimpo = cnpj.replace(/\D/g, '');

        // Exibir indicador de carregamento
        const razaoSocialInput = document.getElementById('razao_social');
        const valorOriginal = razaoSocialInput.value;
        razaoSocialInput.value = '⏳ Consultando CNPJ na Receita Federal...';
        razaoSocialInput.disabled = true;

        // Fazer requisição AJAX
        fetch(`${window.APP_BASE_PATH}/fornecedores/consultar-cnpj/${cnpjLimpo}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Preencher campos automaticamente
                    preencherDadosReceita(data.data);

                    // Mostrar notificação de sucesso
                    mostrarNotificacao('✅ Dados preenchidos automaticamente da Receita Federal!', 'success');
                } else {
                    // Restaurar valor original
                    razaoSocialInput.value = valorOriginal;
                    razaoSocialInput.disabled = false;

                    // Mostrar erro
                    mostrarNotificacao('⚠️ ' + data.message, 'warning');
                }
            })
            .catch(error => {
                console.error('Erro ao consultar CNPJ:', error);

                // Restaurar valor original
                razaoSocialInput.value = valorOriginal;
                razaoSocialInput.disabled = false;

                mostrarNotificacao('❌ Erro ao consultar CNPJ. Verifique sua conexão.', 'error');
            });
    }

    // Função para preencher dados da Receita Federal
    function preencherDadosReceita(dados) {
        // Dados principais
        document.getElementById('razao_social').value = dados.razao_social || '';
        document.getElementById('razao_social').disabled = false;
        document.getElementById('nome_fantasia').value = dados.nome_fantasia || '';

        // CNAE
        if (dados.cnae_fiscal) {
            document.getElementById('cnae').value = dados.cnae_fiscal;
        }

        // Porte da empresa
        if (dados.porte) {
            const porteSelect = document.getElementById('ramo_empresa');
            // Mapear porte para o select
            const mapeamentoPorte = {
                'MEI': 'MEI',
                'ME': 'comercio',
                'EPP': 'comercio',
                'Grande': 'industria'
            };
            if (mapeamentoPorte[dados.porte]) {
                porteSelect.value = mapeamentoPorte[dados.porte];
            }
        }

        // Ramo de atividade
        if (dados.cnae_fiscal_descricao) {
            document.getElementById('ramo_atividade').value = dados.cnae_fiscal_descricao;
        }

        // Contatos
        if (dados.email) {
            document.getElementById('email').value = dados.email;
        }

        if (dados.telefone) {
            document.getElementById('telefone').value = dados.telefone;
        }

        // Endereço
        if (dados.cep) {
            document.getElementById('cep').value = dados.cep;
        }

        if (dados.logradouro) {
            document.getElementById('logradouro').value = dados.logradouro;
        }

        if (dados.numero) {
            document.getElementById('numero').value = dados.numero;
        }

        if (dados.complemento) {
            document.getElementById('complemento').value = dados.complemento;
        }

        if (dados.bairro) {
            document.getElementById('bairro').value = dados.bairro;
        }

        if (dados.municipio) {
            document.getElementById('cidade').value = dados.municipio;
        }

        // Adicionar informação da fonte
        const dadosInfo = document.getElementById('dados_informacao');
        if (dados.fonte) {
            dadosInfo.value = `Dados obtidos via ${dados.fonte} em ${new Date().toLocaleDateString('pt-BR')}`;
        }
    }

    // Função para mostrar notificações
    function mostrarNotificacao(mensagem, tipo) {
        // Criar elemento de notificação
        const notificacao = document.createElement('div');
        notificacao.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            z-index: 99999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            animation: slideInRight 0.3s ease-out;
        `;

        // Cores baseadas no tipo
        const cores = {
            success: { bg: '#10b981', text: 'white' },
            warning: { bg: '#f59e0b', text: 'white' },
            error: { bg: '#ef4444', text: 'white' }
        };

        const cor = cores[tipo] || cores.success;
        notificacao.style.background = cor.bg;
        notificacao.style.color = cor.text;
        notificacao.textContent = mensagem;

        document.body.appendChild(notificacao);

        // Remover após 4 segundos
        setTimeout(() => {
            notificacao.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => notificacao.remove(), 300);
        }, 4000);
    }

    // Função para limpar formulário
    function limparFormularioFornecedor() {
        document.getElementById('razao_social').value = '';
        document.getElementById('nome_fantasia').value = '';
        document.getElementById('tipo_documento').value = 'CNPJ';
        document.getElementById('numero_documento').value = '';
        if (document.getElementById('insc_municipal')) document.getElementById('insc_municipal').value = '';
        if (document.getElementById('insc_estadual')) document.getElementById('insc_estadual').value = '';
        if (document.getElementById('ramo_empresa')) document.getElementById('ramo_empresa').value = '';
        if (document.getElementById('ramo_atividade')) document.getElementById('ramo_atividade').value = '';
        if (document.getElementById('cnae')) document.getElementById('cnae').value = '';
        if (document.getElementById('representante')) document.getElementById('representante').value = '';
        if (document.getElementById('tipo_doc_identificacao')) document.getElementById('tipo_doc_identificacao').value = 'CPF';
        if (document.getElementById('doc_identificacao')) document.getElementById('doc_identificacao').value = '';
        if (document.getElementById('dados_informacao')) document.getElementById('dados_informacao').value = '';
        document.getElementById('telefone').value = '';
        document.getElementById('celular').value = '';
        document.getElementById('email').value = '';
        document.getElementById('site').value = '';
        document.getElementById('cep').value = '';
        document.getElementById('logradouro').value = '';
        document.getElementById('numero').value = '';
        document.getElementById('cidade').value = '';
        document.getElementById('uf').value = '';
        document.getElementById('bairro').value = '';
        document.getElementById('complemento').value = '';

        // Limpar linhas de fornecimento
        const container = document.getElementById('linhas-fornecimento');
        container.innerHTML = 'NENHUMA - ISENÇÃO';
        container.style.textAlign = 'center';
        container.style.padding = '15px';
        container.style.background = '#f9fafb';
        contadorLinhas = 0;
    }

    // Função para preencher formulário com dados do fornecedor
    function preencherFormularioFornecedor(fornecedor) {
        // Campos de identificação
        document.getElementById('razao_social').value = fornecedor.razao_social || '';
        document.getElementById('nome_fantasia').value = fornecedor.nome_fantasia || '';
        document.getElementById('tipo_documento').value = fornecedor.tipo_documento || 'CNPJ';
        document.getElementById('numero_documento').value = fornecedor.numero_documento || fornecedor.cnpj_cpf || '';
        if (document.getElementById('insc_municipal')) document.getElementById('insc_municipal').value = fornecedor.inscricao_municipal || '';
        if (document.getElementById('insc_estadual')) document.getElementById('insc_estadual').value = fornecedor.inscricao_estadual || '';

        // Campos adicionais (se existirem no formulário)
        if (document.getElementById('ramo_empresa')) document.getElementById('ramo_empresa').value = fornecedor.ramo_empresa || '';
        if (document.getElementById('ramo_atividade')) document.getElementById('ramo_atividade').value = fornecedor.ramo_atividade || '';
        if (document.getElementById('cnae')) document.getElementById('cnae').value = fornecedor.cnae || '';
        if (document.getElementById('representante')) document.getElementById('representante').value = fornecedor.representante || '';
        if (document.getElementById('tipo_doc_identificacao')) document.getElementById('tipo_doc_identificacao').value = fornecedor.tipo_doc_id || 'CPF';
        if (document.getElementById('doc_identificacao')) document.getElementById('doc_identificacao').value = fornecedor.doc_identificacao || '';
        if (document.getElementById('dados_informacao')) document.getElementById('dados_informacao').value = fornecedor.dados_info || fornecedor.observacoes || '';

        // Contato
        document.getElementById('telefone').value = fornecedor.telefone || '';
        document.getElementById('celular').value = fornecedor.celular || '';
        document.getElementById('email').value = fornecedor.email || '';
        document.getElementById('site').value = fornecedor.site || '';

        // Endereço
        document.getElementById('cep').value = fornecedor.cep || '';
        document.getElementById('logradouro').value = fornecedor.logradouro || '';
        document.getElementById('numero').value = fornecedor.numero || '';
        document.getElementById('cidade').value = fornecedor.cidade || '';
        document.getElementById('bairro').value = fornecedor.bairro || '';
        if (document.getElementById('uf')) document.getElementById('uf').value = fornecedor.uf || '';
        document.getElementById('complemento').value = fornecedor.complemento || '';

        // Carregar itens de fornecimento (se houver)
        const container = document.getElementById('linhas-fornecimento');
        container.innerHTML = '';
        contadorLinhas = 0;

        if (fornecedor.itens && fornecedor.itens.length > 0) {
            container.style.textAlign = 'left';
            container.style.padding = '0';
            container.style.background = 'transparent';

            fornecedor.itens.forEach(item => {
                contadorLinhas++;
                const novaLinha = document.createElement('div');
                novaLinha.className = 'linha-fornecimento';
                novaLinha.setAttribute('data-linha-id', contadorLinhas);
                novaLinha.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px; margin-bottom: 10px; padding: 10px; background: white; border: 1px solid #e5e7eb; border-radius: 4px; align-items: end;';

                novaLinha.innerHTML = `
                    <div>
                        <label style="display: block; font-size: 11px; color: #374151; font-weight: 500; margin-bottom: 5px;">Descrição do Item/Serviço *</label>
                        <input type="text" name="item_descricao[]" class="item-descricao" required
                            style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;"
                            value="${item.descricao || ''}" placeholder="Ex: Papel A4 sulfite">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: #374151; font-weight: 500; margin-bottom: 5px;">Código CATMAT</label>
                        <input type="text" name="item_catmat[]" class="item-catmat"
                            style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;"
                            value="${item.codigo_catmat || ''}" placeholder="Ex: 123456">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: #374151; font-weight: 500; margin-bottom: 5px;">Unidade *</label>
                        <input type="text" name="item_unidade[]" class="item-unidade" required
                            style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;"
                            value="${item.unidade || 'UNIDADE'}" placeholder="Ex: RESMA">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; color: #374151; font-weight: 500; margin-bottom: 5px;">Preço Ref. (R$)</label>
                        <input type="number" name="item_preco[]" class="item-preco" step="0.01" min="0"
                            style="width: 100%; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;"
                            value="${item.preco_referencia || ''}" placeholder="0.00">
                    </div>
                    <div>
                        <button type="button" class="btn-remover-linha" data-linha-id="${contadorLinhas}"
                            style="background: #ef4444; color: white; border: none; padding: 8px 12px; border-radius: 4px; font-size: 11px; cursor: pointer; height: 32px;"
                            title="Remover linha">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;

                container.appendChild(novaLinha);

                // Adicionar evento de remoção
                novaLinha.querySelector('.btn-remover-linha').addEventListener('click', function() {
                    const linhaId = this.getAttribute('data-linha-id');
                    removerLinhaFornecimento(linhaId);
                });
            });
        } else {
            // Nenhum item, mostrar mensagem padrão
            container.innerHTML = 'NENHUMA - ISENÇÃO';
            container.style.textAlign = 'center';
            container.style.padding = '15px';
            container.style.background = '#f9fafb';
        }
    }

    // Botão Consultar - Abrir modal
    document.getElementById('btn-consultar').addEventListener('click', function() {
        document.getElementById('modal-consultar').style.display = 'block';
        document.getElementById('input-busca-fornecedor').focus();
    });

    // Fechar modal consultar
    document.getElementById('btn-fechar-modal-consultar').addEventListener('click', function() {
        fecharModalConsultar();
    });

    document.getElementById('btn-fechar-consultar').addEventListener('click', function() {
        fecharModalConsultar();
    });

    function fecharModalConsultar() {
        document.getElementById('modal-consultar').style.display = 'none';
        document.getElementById('input-busca-fornecedor').value = '';
        document.getElementById('msg-inicial-consulta').style.display = 'block';
        document.getElementById('resultados-consulta-fornecedor').style.display = 'none';
        document.getElementById('msg-sem-resultados').style.display = 'none';
    }

    // Buscar fornecedor (Enter no campo ou clique no botão)
    document.getElementById('input-busca-fornecedor').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('btn-buscar-fornecedor').click();
        }
    });

    document.getElementById('btn-buscar-fornecedor').addEventListener('click', async function() {
        const termo = document.getElementById('input-busca-fornecedor').value.trim();

        if (!termo) {
            mostrarNotificacao(
                'Campo Vazio',
                'Digite um CNPJ/CPF ou Razão Social para buscar.',
                'warning'
            );
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> BUSCANDO...';

        try {
            // Buscar fornecedores (busca local no banco)
            const response = await fetch(`${window.APP_BASE_PATH}/fornecedores?busca=${encodeURIComponent(termo)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            // Esconder mensagem inicial
            document.getElementById('msg-inicial-consulta').style.display = 'none';

            if (result.success && result.fornecedores && result.fornecedores.length > 0) {
                // Exibir resultados
                exibirResultadosConsulta(result.fornecedores);
                document.getElementById('resultados-consulta-fornecedor').style.display = 'block';
                document.getElementById('msg-sem-resultados').style.display = 'none';
            } else {
                // Sem resultados
                document.getElementById('resultados-consulta-fornecedor').style.display = 'none';
                document.getElementById('msg-sem-resultados').style.display = 'block';
            }

        } catch (error) {
            console.error('Erro ao buscar fornecedores:', error);

            let detalhes = `Termo buscado: ${termo}\n`;
            detalhes += `URL: fornecedores?busca=${encodeURIComponent(termo)}\n`;
            detalhes += `Erro: ${error.message}\n\n`;
            if (error.stack) {
                detalhes += `Stack Trace:\n${error.stack}`;
            }

            mostrarNotificacao(
                'Erro ao Buscar Fornecedores',
                'Não foi possível realizar a busca. Tente novamente.',
                'error',
                detalhes
            );
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search"></i> BUSCAR';
        }
    });

    function exibirResultadosConsulta(fornecedores) {
        const tbody = document.getElementById('tbody-resultados-consulta');
        tbody.innerHTML = '';

        fornecedores.forEach(fornecedor => {
            const tr = document.createElement('tr');
            tr.style.cssText = 'border-bottom: 1px solid #e5e7eb;';
            tr.innerHTML = `
                <td style="padding: 10px;">${fornecedor.numero_documento_formatado || fornecedor.numero_documento}</td>
                <td style="padding: 10px;">${fornecedor.razao_social}</td>
                <td style="padding: 10px;">${fornecedor.cidade || '-'}</td>
                <td style="padding: 10px; text-align: center;">
                    <button class="btn-visualizar-consulta" data-id="${fornecedor.id}"
                        style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 11px; cursor: pointer; margin-right: 5px;">
                        <i class="fas fa-eye"></i> VER
                    </button>
                    <button class="btn-editar-consulta" data-id="${fornecedor.id}"
                        style="background: #f59e0b; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 11px; cursor: pointer;">
                        <i class="fas fa-edit"></i> EDITAR
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Adicionar eventos aos botões
        document.querySelectorAll('.btn-visualizar-consulta').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');

                try {
                    // Buscar dados do fornecedor
                    const response = await fetch(`${window.APP_BASE_PATH}/fornecedores/${id}`, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' }
                    });

                    const result = await response.json();

                    if (result.success && result.fornecedor) {
                        // Fechar modal consultar
                        fecharModalConsultar();

                        // Preencher e abrir modal de visualização
                        preencherModalVisualizacao(result.fornecedor);
                        document.getElementById('modal-visualizar-fornecedor').style.display = 'block';
                    } else {
                        mostrarNotificacao(
                            'Erro',
                            'Não foi possível carregar os dados do fornecedor.',
                            'error'
                        );
                    }
                } catch (error) {
                    console.error('Erro ao carregar fornecedor:', error);
                    mostrarNotificacao(
                        'Erro ao Carregar Fornecedor',
                        'Ocorreu um erro ao buscar os dados do fornecedor. Tente novamente.',
                        'error',
                        `ID: ${id}\nErro: ${error.message}`
                    );
                }
            });
        });

        document.querySelectorAll('.btn-editar-consulta').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');

                try {
                    // Buscar dados do fornecedor
                    const response = await fetch(`${window.APP_BASE_PATH}/fornecedores/${id}`, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' }
                    });

                    const result = await response.json();

                    if (result.success && result.fornecedor) {
                        // Fechar modal consultar
                        fecharModalConsultar();

                        // Abrir modal editar
                        modoEdicao = true;
                        fornecedorIdEditando = id;
                        document.getElementById('titulo-modal-fornecedor').textContent = 'EDITAR FORNECEDOR';
                        preencherFormularioFornecedor(result.fornecedor);
                        document.getElementById('modal-adicionar').style.display = 'block';
                    }
                } catch (error) {
                    console.error('Erro ao carregar fornecedor:', error);

                    let detalhes = `ID: ${id}\n`;
                    detalhes += `URL: fornecedores/${id}\n`;
                    detalhes += `Erro: ${error.message}\n\n`;
                    if (error.stack) {
                        detalhes += `Stack Trace:\n${error.stack}`;
                    }

                    mostrarNotificacao(
                        'Erro ao Carregar Fornecedor',
                        'Não foi possível carregar os dados do fornecedor para edição.',
                        'error',
                        detalhes
                    );
                }
            });
        });
    }

    // Botão Importar - Abrir modal
    document.getElementById('btn-importar').addEventListener('click', function() {
        document.getElementById('modal-importar').style.display = 'block';
    });

    // Fechar modal importar
    document.getElementById('btn-fechar-modal-importar').addEventListener('click', function() {
        document.getElementById('modal-importar').style.display = 'none';
        resetarImportacao();
    });

    document.getElementById('btn-cancelar-importar').addEventListener('click', function() {
        document.getElementById('modal-importar').style.display = 'none';
        resetarImportacao();
    });

    // Botão IMPORTAR - Executa a importação
    document.getElementById('btn-importar-salvar').addEventListener('click', function() {
        const arquivoInput = document.getElementById('arquivo-importacao');
        if (arquivoInput.files.length > 0) {
            iniciarImportacao(arquivoInput.files[0]);
        }
    });

    // Função para resetar o estado da importação
    function resetarImportacao() {
        document.getElementById('arquivo-importacao').value = '';
        document.getElementById('arquivo-selecionado').style.display = 'none';
        document.getElementById('progresso-importacao').style.display = 'none';
        document.getElementById('resultado-importacao').style.display = 'none';
        document.getElementById('barra-progresso').style.width = '0%';

        // Desabilitar botão IMPORTAR
        const btnImportar = document.getElementById('btn-importar-salvar');
        btnImportar.disabled = true;
        btnImportar.style.opacity = '0.5';
        btnImportar.style.cursor = 'not-allowed';
    }

    // Upload de arquivo - Click
    document.getElementById('upload-area').addEventListener('click', function() {
        document.getElementById('arquivo-importacao').click();
    });

    // Upload de arquivo - Drag and Drop
    const uploadArea = document.getElementById('upload-area');

    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#3b82f6';
        this.style.background = '#eff6ff';
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#d1d5db';
        this.style.background = '#f9fafb';
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#d1d5db';
        this.style.background = '#f9fafb';

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('arquivo-importacao').files = files;
            exibirArquivoSelecionado(files[0]);
        }
    });

    // Arquivo selecionado
    document.getElementById('arquivo-importacao').addEventListener('change', function(e) {
        if (this.files.length > 0) {
            exibirArquivoSelecionado(this.files[0]);
        }
    });

    // Exibir informações do arquivo selecionado
    function exibirArquivoSelecionado(file) {
        const nomeArquivo = file.name;
        const tamanhoMB = (file.size / (1024 * 1024)).toFixed(2);

        document.getElementById('nome-arquivo').textContent = nomeArquivo;
        document.getElementById('tamanho-arquivo').textContent = `${tamanhoMB} MB`;
        document.getElementById('arquivo-selecionado').style.display = 'block';

        // Habilitar botão IMPORTAR
        const btnImportar = document.getElementById('btn-importar-salvar');
        btnImportar.disabled = false;
        btnImportar.style.opacity = '1';
        btnImportar.style.cursor = 'pointer';
    }

    // Remover arquivo
    document.getElementById('btn-remover-arquivo').addEventListener('click', function() {
        document.getElementById('arquivo-importacao').value = '';
        document.getElementById('arquivo-selecionado').style.display = 'none';
        document.getElementById('progresso-importacao').style.display = 'none';
        document.getElementById('resultado-importacao').style.display = 'none';

        // Desabilitar botão IMPORTAR
        const btnImportar = document.getElementById('btn-importar-salvar');
        btnImportar.disabled = true;
        btnImportar.style.opacity = '0.5';
        btnImportar.style.cursor = 'not-allowed';
    });

    // Iniciar importação
    function iniciarImportacao(file) {
        // Validar tamanho
        if (file.size > 10 * 1024 * 1024) {
            mostrarNotificacao(
                'Arquivo Muito Grande',
                `O arquivo tem ${(file.size / 1024 / 1024).toFixed(2)}MB. Tamanho máximo permitido: 10MB`,
                'warning'
            );
            return;
        }

        // Validar extensão
        const extensao = file.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls', 'csv'].includes(extensao)) {
            mostrarNotificacao(
                'Formato Inválido',
                `Extensão ".${extensao}" não é suportada. Use: .xlsx, .xls ou .csv`,
                'warning'
            );
            return;
        }

        // Exibir progresso
        document.getElementById('progresso-importacao').style.display = 'block';
        document.getElementById('resultado-importacao').style.display = 'none';

        // Preparar dados do formulário
        const formData = new FormData();
        formData.append('planilha', file);

        // Enviar para o backend
        (async () => {
            try {
                const response = await fetch(`${window.APP_BASE_PATH}/fornecedores/importar`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    finalizarImportacao(result.importados || 0, result.erros?.length || 0);
                } else {
                    let detalhes = `Arquivo: ${file.name}\n`;
                    detalhes += `Tamanho: ${(file.size / 1024).toFixed(2)} KB\n`;
                    detalhes += `Status: ${response.status}\n\n`;
                    detalhes += `Resposta do servidor:\n${JSON.stringify(result, null, 2)}`;

                    mostrarNotificacao(
                        'Erro ao Importar',
                        result.message || 'Erro desconhecido ao processar a planilha',
                        'error',
                        detalhes
                    );
                    document.getElementById('progresso-importacao').style.display = 'none';
                }
            } catch (error) {
                console.error('Erro na importação:', error);

                let detalhes = `Arquivo: ${file.name}\n`;
                detalhes += `Tamanho: ${(file.size / 1024).toFixed(2)} KB\n`;
                detalhes += `URL: fornecedores/importar\n`;
                detalhes += `Erro: ${error.message}\n\n`;
                if (error.stack) {
                    detalhes += `Stack Trace:\n${error.stack}`;
                }

                mostrarNotificacao(
                    'Erro ao Importar Planilha',
                    'Ocorreu um erro ao processar a planilha. Veja os detalhes técnicos abaixo.',
                    'error',
                    detalhes
                );
                document.getElementById('progresso-importacao').style.display = 'none';
            }
        })();
    }

    // Finalizar importação
    function finalizarImportacao(sucesso, erros) {
        document.getElementById('progresso-importacao').style.display = 'none';

        let html = '<div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 15px; border-radius: 4px;">';
        html += `<p style="margin: 0; font-size: 13px; color: #065f46; font-weight: 600;"><i class="fas fa-check-circle"></i> Importação concluída!</p>`;
        html += `<p style="margin: 8px 0 0 0; font-size: 12px; color: #047857;">`;
        html += `✅ <strong>${sucesso} fornecedores</strong> importados com sucesso!`;
        if (erros > 0) {
            html += `<br>⚠️ ${erros} linhas com erro (ignoradas)`;
        }
        html += `</p>`;
        html += `<p style="margin: 10px 0 0 0; font-size: 11px; color: #6b7280;"><em>O sistema detectou automaticamente as colunas da planilha.</em></p>`;
        html += '</div>';

        document.getElementById('resultado-importacao').innerHTML = html;
        document.getElementById('resultado-importacao').style.display = 'block';

        // Recarregar tabela após 2 segundos
        setTimeout(() => {
            location.reload();
        }, 2000);
    }

    // Download da planilha modelo
    document.getElementById('btn-download-modelo').addEventListener('click', function() {
        // Fazer download diretamente
        window.location.href = 'fornecedores/modelo-planilha';
    });

    // Botões Editar - Abrir modal em modo edição
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.getAttribute('data-id');

            // Configurar modo edição
            modoEdicao = true;
            fornecedorIdEditando = id;
            document.getElementById('titulo-modal-fornecedor').textContent = 'EDITAR FORNECEDOR';

            // Buscar dados do fornecedor via AJAX
            try {
                const response = await fetch(`${window.APP_BASE_PATH}/fornecedores/${id}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const result = await response.json();

                if (result.success && result.fornecedor) {
                    // Preencher formulário com dados reais
                    preencherFormularioFornecedor(result.fornecedor);

                    // Abrir modal
                    document.getElementById('modal-adicionar').style.display = 'block';
                } else {
                    let detalhes = `ID: ${id}\n`;
                    detalhes += `URL: fornecedores/${id}\n`;
                    detalhes += `Status: ${response.status}\n\n`;
                    detalhes += `Resposta do servidor:\n${JSON.stringify(result, null, 2)}`;

                    mostrarNotificacao(
                        'Erro ao Carregar Fornecedor',
                        'Não foi possível carregar os dados do fornecedor.',
                        'error',
                        detalhes
                    );
                }
            } catch (error) {
                console.error('Erro ao buscar fornecedor:', error);

                let detalhes = `ID: ${id}\n`;
                detalhes += `URL: fornecedores/${id}\n`;
                detalhes += `Erro: ${error.message}\n\n`;
                if (error.stack) {
                    detalhes += `Stack Trace:\n${error.stack}`;
                }

                mostrarNotificacao(
                    'Erro ao Carregar Fornecedor',
                    'Ocorreu um erro ao buscar os dados do fornecedor.',
                    'error',
                    detalhes
                );
            }
        });
    });

    // Botões Excluir - Abrir modal de confirmação
    document.querySelectorAll('.btn-excluir').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.getAttribute('data-id');
            fornecedorIdExcluindo = id;

            // Buscar nome do fornecedor via AJAX
            try {
                const response = await fetch(`${window.APP_BASE_PATH}/fornecedores/${id}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const result = await response.json();
                const nomeFornecedor = result.fornecedor?.razao_social || 'Fornecedor desconhecido';

                // Exibir no modal
                document.getElementById('nome-fornecedor-excluir').textContent = nomeFornecedor;

                // Abrir modal (usar flex para centralizar)
                const modalExcluir = document.getElementById('modal-excluir');
                modalExcluir.style.display = 'flex';
            } catch (error) {
                console.error('Erro ao buscar fornecedor:', error);
                // Abrir modal mesmo se houver erro
                document.getElementById('nome-fornecedor-excluir').textContent = 'Fornecedor';
                const modalExcluir = document.getElementById('modal-excluir');
                modalExcluir.style.display = 'flex';
            }
        });
    });

    // Cancelar exclusão
    document.getElementById('btn-cancelar-excluir').addEventListener('click', function() {
        document.getElementById('modal-excluir').style.display = 'none';
        fornecedorIdExcluindo = null;
    });

    // Confirmar exclusão
    document.getElementById('btn-confirmar-excluir').addEventListener('click', function() {
        if (fornecedorIdExcluindo) {
            // Desabilitar botão durante exclusão
            const btnExcluir = this;
            const textoOriginal = btnExcluir.innerHTML;
            btnExcluir.disabled = true;
            btnExcluir.innerHTML = '<i class="fas fa-spinner fa-spin"></i> EXCLUINDO...';

            // Fazer requisição DELETE via AJAX
            fetch(`${window.APP_BASE_PATH}/fornecedores/${fornecedorIdExcluindo}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Resetar botão ANTES de fechar modal
                    btnExcluir.disabled = false;
                    btnExcluir.innerHTML = textoOriginal;

                    // Fechar modal
                    document.getElementById('modal-excluir').style.display = 'none';

                    // Mostrar notificação de sucesso
                    mostrarNotificacao('✅ ' + data.message, 'success');

                    // Remover linha da tabela visualmente (sem reload)
                    const btnExcluirTabela = document.querySelector(`.btn-excluir[data-id="${fornecedorIdExcluindo}"]`);
                    if (btnExcluirTabela) {
                        const linha = btnExcluirTabela.closest('tr');
                        linha.style.animation = 'fadeOut 0.3s ease-out';
                        setTimeout(() => {
                            linha.remove();
                        }, 300);
                    }

                    fornecedorIdExcluindo = null;
                } else {
                    // Mostrar erro
                    mostrarNotificacao('❌ ' + data.message, 'error');
                    btnExcluir.disabled = false;
                    btnExcluir.innerHTML = textoOriginal;
                }
            })
            .catch(error => {
                console.error('Erro ao excluir fornecedor:', error);
                mostrarNotificacao('❌ Erro ao excluir fornecedor. Tente novamente.', 'error');
                btnExcluir.disabled = false;
                btnExcluir.innerHTML = textoOriginal;
            });
        }
    });

    // Filtro de busca (placeholder)
    document.getElementById('filtro_nome').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            const filtro = this.value;
            console.log('Buscando por:', filtro);
            // Funcionalidade de busca será implementada
        }
    });

    // ==================================================
    // FUNÇÕES PARA MODAL DE VISUALIZAÇÃO DE FORNECEDOR
    // ==================================================

    /**
     * Preenche o modal de visualização com os dados do fornecedor
     */
    window.preencherModalVisualizacao = function(fornecedor) {
        // Informações Básicas
        document.getElementById('visualizar-razao-social').textContent = fornecedor.razao_social || '-';
        document.getElementById('visualizar-nome-fantasia').textContent = fornecedor.nome_fantasia || '-';
        document.getElementById('visualizar-cnpj').textContent = fornecedor.numero_documento_formatado || fornecedor.numero_documento || '-';

        // Determinar porte da empresa
        let porte = '-';
        if (fornecedor.porte_empresa) {
            const porteMap = {
                'ME': 'Microempresa (ME)',
                'EPP': 'Empresa de Pequeno Porte (EPP)',
                'DEMAIS': 'Demais (Médio/Grande Porte)',
                'MEI': 'Microempreendedor Individual (MEI)'
            };
            porte = porteMap[fornecedor.porte_empresa] || fornecedor.porte_empresa;
        }
        document.getElementById('visualizar-porte').textContent = porte;

        // Contato
        document.getElementById('visualizar-email').textContent = fornecedor.email || '-';
        document.getElementById('visualizar-telefone').textContent = fornecedor.telefone || '-';

        // Endereço
        document.getElementById('visualizar-logradouro').textContent = fornecedor.logradouro || '-';
        document.getElementById('visualizar-numero').textContent = fornecedor.numero || '-';
        document.getElementById('visualizar-complemento').textContent = fornecedor.complemento || '-';
        document.getElementById('visualizar-bairro').textContent = fornecedor.bairro || '-';
        document.getElementById('visualizar-cidade').textContent = fornecedor.cidade || '-';
        document.getElementById('visualizar-uf').textContent = fornecedor.uf || '-';
        document.getElementById('visualizar-cep').textContent = fornecedor.cep || '-';

        // Itens Fornecidos
        const listaItens = document.getElementById('visualizar-lista-itens');
        const totalItens = document.getElementById('visualizar-total-itens');

        if (fornecedor.itens && fornecedor.itens.length > 0) {
            totalItens.textContent = fornecedor.itens.length;

            let html = '<div style="border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden;">';
            html += '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">';
            html += '<thead style="background: #f3f4f6;">';
            html += '<tr>';
            html += '<th style="padding: 8px 10px; text-align: left; font-weight: 600; color: #6b7280;">DESCRIÇÃO</th>';
            html += '<th style="padding: 8px 10px; text-align: center; font-weight: 600; color: #6b7280; width: 100px;">UNIDADE</th>';
            html += '<th style="padding: 8px 10px; text-align: right; font-weight: 600; color: #6b7280; width: 120px;">PREÇO</th>';
            html += '</tr>';
            html += '</thead>';
            html += '<tbody>';

            fornecedor.itens.forEach((item, index) => {
                const bgColor = index % 2 === 0 ? '#ffffff' : '#f9fafb';
                html += `<tr style="background: ${bgColor}; border-top: 1px solid #e5e7eb;">`;
                html += `<td style="padding: 10px; color: #374151;">${item.descricao || '-'}</td>`;
                html += `<td style="padding: 10px; text-align: center; color: #374151;">${item.unidade_medida || '-'}</td>`;

                const preco = item.preco_unitario ? parseFloat(item.preco_unitario).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '-';
                html += `<td style="padding: 10px; text-align: right; color: #10b981; font-weight: 600;">${preco}</td>`;
                html += '</tr>';
            });

            html += '</tbody>';
            html += '</table>';
            html += '</div>';

            listaItens.innerHTML = html;
        } else {
            totalItens.textContent = '0';
            listaItens.innerHTML = '<p style="color: #6b7280; font-size: 13px; text-align: center; padding: 20px;">Nenhum item cadastrado</p>';
        }

        // Armazenar ID do fornecedor para botão EDITAR
        document.getElementById('btn-editar-do-visualizar').setAttribute('data-id', fornecedor.id);
    };

    /**
     * Fecha o modal de visualização
     */
    function fecharModalVisualizacao() {
        document.getElementById('modal-visualizar-fornecedor').style.display = 'none';
    }

    // Eventos para fechar modal de visualização
    document.getElementById('btn-fechar-modal-visualizar').addEventListener('click', fecharModalVisualizacao);
    document.getElementById('btn-fechar-modal-visualizar-footer').addEventListener('click', fecharModalVisualizacao);

    // Fechar modal ao clicar no fundo
    document.getElementById('modal-visualizar-fornecedor').addEventListener('click', function(e) {
        if (e.target === this) {
            fecharModalVisualizacao();
        }
    });

    // Botão EDITAR dentro do modal de visualização
    document.getElementById('btn-editar-do-visualizar').addEventListener('click', async function() {
        const id = this.getAttribute('data-id');

        try {
            // Buscar dados do fornecedor novamente (garantir dados atualizados)
            const response = await fetch(`${window.APP_BASE_PATH}/fornecedores/${id}`, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (result.success && result.fornecedor) {
                // Fechar modal de visualização
                fecharModalVisualizacao();

                // Abrir modal de edição
                modoEdicao = true;
                fornecedorIdEditando = id;
                document.getElementById('titulo-modal-fornecedor').textContent = 'EDITAR FORNECEDOR';
                preencherFormularioFornecedor(result.fornecedor);
                document.getElementById('modal-adicionar').style.display = 'block';
            } else {
                mostrarNotificacao(
                    'Erro',
                    'Não foi possível carregar os dados do fornecedor para edição.',
                    'error'
                );
            }
        } catch (error) {
            console.error('Erro ao carregar fornecedor para edição:', error);
            mostrarNotificacao(
                'Erro ao Carregar Fornecedor',
                'Ocorreu um erro ao buscar os dados do fornecedor. Tente novamente.',
                'error',
                `ID: ${id}\nErro: ${error.message}`
            );
        }
    });
});
</script>

<style>
/* Hover nos botões do modal de excluir */
#btn-cancelar-excluir:hover {
    background: #d1d5db !important;
}

#btn-confirmar-excluir:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
}

/* Hover nos botões do modal de cadastro */
#btn-cancelar-fornecedor:hover {
    background: #d1d5db !important;
}

#btn-salvar-fornecedor:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

/* Animação de entrada do modal */
#modal-excluir {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Animações para notificações */
@keyframes slideInRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

/* Animação para remover linha da tabela */
@keyframes fadeOut {
    from {
        opacity: 1;
        transform: scale(1);
    }
    to {
        opacity: 0;
        transform: scale(0.95);
    }
}
</style>

@endsection
