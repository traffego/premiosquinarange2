<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Cotas Premiadas</title>
    <style>
        .form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-title {
            color: #3659db;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e4e8;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3659db;
            box-shadow: 0 0 0 3px rgba(54, 89, 219, 0.1);
        }
        
        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #3659db;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 13px;
            color: #333;
        }
        
        .info-box strong {
            color: #3659db;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 13px;
            color: #856404;
        }
        
        .btn-gerar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-gerar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-gerar:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3659db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #3659db;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-title">
            🎯 Gerar Cotas Premiadas
        </div>
        <div class="form-subtitle">
            Adicione automaticamente cotas premiadas a um sorteio ativo
        </div>
        
        <div id="alert-container"></div>
        
        <form id="form-gerar-cotas">
            <!-- Seleção do Sorteio -->
            <div class="form-group">
                <label class="form-label" for="sorteio">
                    1️⃣ Para qual sorteio deseja cadastrar cotas premiadas?
                </label>
                <select class="form-control" id="sorteio" name="sorteio" required>
                    <option value="">Selecione um sorteio...</option>
                    <?php
                    // Buscar sorteios ativos
                    $sql = "SELECT id, name, qty_numbers, paid_numbers, pending_numbers, cotas_premiadas 
                            FROM product_list 
                            WHERE status = 1 
                            ORDER BY id DESC";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $disponiveis = $row['qty_numbers'] - $row['paid_numbers'] - $row['pending_numbers'];
                            
                            // Contar cotas premiadas já cadastradas
                            $cotas_premiadas_count = 0;
                            if (!empty($row['cotas_premiadas'])) {
                                $cotas_premiadas_array = explode(',', $row['cotas_premiadas']);
                                $cotas_premiadas_count = count($cotas_premiadas_array);
                            }
                            
                            echo "<option value='{$row['id']}' 
                                    data-total='{$row['qty_numbers']}' 
                                    data-vendidos='{$row['paid_numbers']}'
                                    data-reservados='{$row['pending_numbers']}'
                                    data-disponiveis='{$disponiveis}'
                                    data-premiadas='{$cotas_premiadas_count}'>
                                    {$row['name']} (Disponíveis: {$disponiveis})
                                  </option>";
                        }
                    } else {
                        echo "<option value=''>Nenhum sorteio ativo encontrado</option>";
                    }
                    ?>
                </select>
            </div>
            
            <!-- Informações do Sorteio -->
            <div id="sorteio-info" style="display: none;">
                <div class="info-box">
                    <strong>📊 Informações do Sorteio:</strong>
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-value" id="stat-total">0</div>
                            <div class="stat-label">Total de Números</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="stat-vendidos">0</div>
                            <div class="stat-label">Vendidos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="stat-disponiveis">0</div>
                            <div class="stat-label">Disponíveis</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="stat-premiadas">0</div>
                            <div class="stat-label">Cotas Premiadas</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quantidade -->
            <div class="form-group">
                <label class="form-label" for="quantidade">
                    2️⃣ Quantidade de cotas premiadas a gerar
                </label>
                <input type="number" 
                       class="form-control" 
                       id="quantidade" 
                       name="quantidade" 
                       min="1" 
                       placeholder="Ex: 10"
                       required>
                <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">
                    O sistema gerará apenas números disponíveis (não vendidos e não premiados)
                </small>
            </div>
            
            <!-- Prêmio -->
            <div class="form-group">
                <label class="form-label" for="premio">
                    3️⃣ Qual o prêmio dessas cotas?
                </label>
                <input type="text" 
                       class="form-control" 
                       id="premio" 
                       name="premio" 
                       placeholder="Ex: Pix R$ 50,00"
                       required>
                <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">
                    Descreva o prêmio que será exibido ao cliente
                </small>
            </div>
            
            <div class="warning-box">
                ⚠️ <strong>Atenção:</strong> As cotas serão geradas aleatoriamente entre os números disponíveis. 
                O sistema automaticamente evitará números já vendidos ou já cadastrados como premiados.
            </div>
            
            <button type="submit" class="btn-gerar" id="btn-submit">
                <span>✨ Gerar Cotas Premiadas</span>
            </button>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p style="margin-top: 10px; color: #666;">Gerando cotas premiadas...</p>
            </div>
        </form>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Atualizar informações ao selecionar sorteio
            $('#sorteio').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const disponiveis = parseInt(selectedOption.data('disponiveis')) || 0;
                
                if ($(this).val()) {
                    $('#sorteio-info').show();
                    $('#stat-total').text(selectedOption.data('total') || 0);
                    $('#stat-vendidos').text(selectedOption.data('vendidos') || 0);
                    $('#stat-disponiveis').text(disponiveis);
                    $('#stat-premiadas').text(selectedOption.data('premiadas') || 0);
                    
                    // Atualizar max da quantidade
                    $('#quantidade').attr('max', disponiveis);
                } else {
                    $('#sorteio-info').hide();
                }
            });
            
            // Validar quantidade
            $('#quantidade').on('input', function() {
                const selectedOption = $('#sorteio').find('option:selected');
                const disponiveis = parseInt(selectedOption.data('disponiveis')) || 0;
                const quantidade = parseInt($(this).val()) || 0;
                
                if (quantidade > disponiveis) {
                    showAlert('error', `❌ Quantidade não pode ser maior que ${disponiveis} (números disponíveis)`);
                    $(this).val(disponiveis);
                }
            });
            
            // Submeter formulário
            $('#form-gerar-cotas').on('submit', function(e) {
                e.preventDefault();
                
                const sorteioId = $('#sorteio').val();
                const quantidade = parseInt($('#quantidade').val());
                const premio = $('#premio').val().trim();
                
                // Validações
                if (!sorteioId) {
                    showAlert('error', '❌ Selecione um sorteio');
                    return;
                }
                
                if (quantidade < 1) {
                    showAlert('error', '❌ Quantidade deve ser maior que zero');
                    return;
                }
                
                if (!premio) {
                    showAlert('error', '❌ Informe o prêmio');
                    return;
                }
                
                const selectedOption = $('#sorteio').find('option:selected');
                const disponiveis = parseInt(selectedOption.data('disponiveis')) || 0;
                
                if (quantidade > disponiveis) {
                    showAlert('error', `❌ Quantidade não pode ser maior que ${disponiveis} (números disponíveis)`);
                    return;
                }
                
                // Confirmar ação
                if (!confirm(`Deseja gerar ${quantidade} cotas premiadas com o prêmio "${premio}"?`)) {
                    return;
                }
                
                // Mostrar loading
                $('#btn-submit').prop('disabled', true);
                $('#loading').show();
                $('#alert-container').html('');
                
                // Enviar requisição
                $.ajax({
                    url: _base_url_ + 'class/Main.php?action=generate_cotas_premiadas',
                    method: 'POST',
                    data: {
                        sorteio_id: sorteioId,
                        quantidade: quantidade,
                        premio: premio
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loading').hide();
                        $('#btn-submit').prop('disabled', false);
                        
                        if (response.status === 'success') {
                            showAlert('success', `✅ ${response.msg}<br><br><strong>Cotas geradas:</strong> ${response.cotas_geradas.join(', ')}`);
                            
                            // Limpar formulário
                            $('#quantidade').val('');
                            $('#premio').val('');
                            
                            // Recarregar página após 3 segundos
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        } else {
                            showAlert('error', '❌ ' + (response.error || 'Erro ao gerar cotas premiadas'));
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loading').hide();
                        $('#btn-submit').prop('disabled', false);
                        showAlert('error', '❌ Erro na comunicação com o servidor: ' + error);
                    }
                });
            });
            
            function showAlert(type, message) {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
                const alertHtml = `<div class="alert ${alertClass}">${message}</div>`;
                $('#alert-container').html(alertHtml);
                $('.alert').fadeIn();
                
                // Scroll para o topo
                $('html, body').animate({ scrollTop: 0 }, 300);
            }
        });
    </script>
    
    <!-- Seção de Exclusão de Cotas -->
    <div class="form-container" style="margin-top: 40px; border-left: 4px solid #dc3545;">
        <div class="form-title" style="color: #dc3545;">
            🗑️ Excluir Cotas Premiadas
        </div>
        <div class="form-subtitle">
            Remover cotas premiadas em lote (apenas cotas não vendidas)
        </div>
        
        <div id="alert-container-delete"></div>
        
        <form id="form-excluir-cotas">
            <!-- Seleção do Sorteio -->
            <div class="form-group">
                <label class="form-label" for="sorteio_excluir">
                    1️⃣ De qual sorteio deseja excluir cotas premiadas?
                </label>
                <select class="form-control" id="sorteio_excluir" name="sorteio_excluir" required>
                    <option value="">Selecione um sorteio...</option>
                    <?php
                    // Reabrir conexão se necessário
                    include('../../initialize.php');
                    
                    // Buscar sorteios com cotas premiadas
                    $sql = "SELECT id, name, cotas_premiadas 
                            FROM product_list 
                            WHERE status = 1 
                            AND cotas_premiadas IS NOT NULL 
                            AND cotas_premiadas != ''
                            ORDER BY id DESC";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $cotas_count = 0;
                            if (!empty($row['cotas_premiadas'])) {
                                $cotas_array = explode(',', $row['cotas_premiadas']);
                                $cotas_count = count($cotas_array);
                            }
                            
                            echo "<option value='{$row['id']}' data-cotas-total='{$cotas_count}'>
                                    {$row['name']} ({$cotas_count} cotas cadastradas)
                                  </option>";
                        }
                    } else {
                        echo "<option value=''>Nenhum sorteio com cotas premiadas</option>";
                    }
                    ?>
                </select>
            </div>
            
            <!-- Informações do Sorteio para Exclusão -->
            <div id="sorteio-info-delete" style="display: none;">
                <div class="warning-box">
                    <strong>📊 Cotas Premiadas no Sorteio:</strong>
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-value" id="stat-cotas-total-delete">0</div>
                            <div class="stat-label">Total Cadastradas</div>
                        </div>
                        <div class="stat-card" style="background: #d4edda;">
                            <div class="stat-value" id="stat-cotas-disponiveis-delete" style="color: #155724;">0</div>
                            <div class="stat-label">Disponíveis (Não Vendidas)</div>
                        </div>
                        <div class="stat-card" style="background: #f8d7da;">
                            <div class="stat-value" id="stat-cotas-vendidas-delete" style="color: #721c24;">0</div>
                            <div class="stat-label">Vendidas (Protegidas)</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quantidade -->
            <div class="form-group">
                <label class="form-label" for="quantidade_excluir">
                    2️⃣ Quantas cotas premiadas deseja excluir?
                </label>
                <input type="number" 
                       class="form-control" 
                       id="quantidade_excluir" 
                       name="quantidade_excluir" 
                       min="1" 
                       placeholder="Ex: 10"
                       required>
                <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">
                    ⚠️ Serão excluídas apenas cotas não vendidas (disponíveis)
                </small>
            </div>
            
            <div class="warning-box" style="border-color: #dc3545; background: #fff5f5;">
                ⚠️ <strong>Atenção:</strong> Esta ação não pode ser desfeita! O sistema excluirá as cotas mais recentes 
                que ainda não foram vendidas. Cotas já vendidas ou pagas NÃO serão removidas.
            </div>
            
            <button type="submit" class="btn-gerar" id="btn-submit-delete" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                <span>🗑️ Excluir Cotas Premiadas</span>
            </button>
            
            <div class="loading" id="loading-delete">
                <div class="spinner"></div>
                <p style="margin-top: 10px; color: #666;">Excluindo cotas premiadas...</p>
            </div>
        </form>
    </div>
    
    <script>
        // JavaScript para exclusão de cotas
        $(document).ready(function() {
            // Atualizar informações ao selecionar sorteio para excluir
            $('#sorteio_excluir').on('change', function() {
                const sorteioId = $(this).val();
                const selectedOption = $(this).find('option:selected');
                const cotasTotal = parseInt(selectedOption.data('cotas-total')) || 0;
                
                if (sorteioId) {
                    // Buscar informações detalhadas via AJAX
                    $.ajax({
                        url: _base_url_ + 'class/Main.php?action=get_cotas_info',
                        method: 'POST',
                        data: { sorteio_id: sorteioId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                $('#sorteio-info-delete').show();
                                $('#stat-cotas-total-delete').text(response.total);
                                $('#stat-cotas-disponiveis-delete').text(response.disponiveis);
                                $('#stat-cotas-vendidas-delete').text(response.vendidas);
                                
                                // Atualizar max da quantidade
                                $('#quantidade_excluir').attr('max', response.disponiveis);
                            }
                        }
                    });
                } else {
                    $('#sorteio-info-delete').hide();
                }
            });
            
            // Validar quantidade ao digitar
            $('#quantidade_excluir').on('input', function() {
                const maxDisponiveis = parseInt($(this).attr('max')) || 0;
                const quantidade = parseInt($(this).val()) || 0;
                
                if (quantidade > maxDisponiveis) {
                    showAlertDelete('error', `❌ Quantidade não pode ser maior que ${maxDisponiveis} (cotas disponíveis para exclusão)`);
                    $(this).val(maxDisponiveis);
                }
            });
            
            // Submeter formulário de exclusão
            $('#form-excluir-cotas').on('submit', function(e) {
                e.preventDefault();
                
                const sorteioId = $('#sorteio_excluir').val();
                const quantidade = parseInt($('#quantidade_excluir').val());
                
                // Validações
                if (!sorteioId) {
                    showAlertDelete('error', '❌ Selecione um sorteio');
                    return;
                }
                
                if (quantidade < 1) {
                    showAlertDelete('error', '❌ Quantidade deve ser maior que zero');
                    return;
                }
                
                // Confirmar ação
                if (!confirm(`⚠️ ATENÇÃO!\n\nDeseja realmente excluir ${quantidade} cota(s) premiada(s)?\n\nEsta ação NÃO pode ser desfeita!\n\nApenas cotas não vendidas serão removidas.`)) {
                    return;
                }
                
                // Mostrar loading
                $('#btn-submit-delete').prop('disabled', true);
                $('#loading-delete').show();
                $('#alert-container-delete').html('');
                
                // Enviar requisição
                $.ajax({
                    url: _base_url_ + 'class/Main.php?action=delete_cotas_premiadas_batch',
                    method: 'POST',
                    data: {
                        sorteio_id: sorteioId,
                        quantidade: quantidade
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loading-delete').hide();
                        $('#btn-submit-delete').prop('disabled', false);
                        
                        if (response.status === 'success') {
                            let message = `✅ ${response.msg}<br><br>`;
                            if (response.cotas_excluidas && response.cotas_excluidas.length > 0) {
                                message += `<strong>Cotas excluídas:</strong> ${response.cotas_excluidas.join(', ')}`;
                            }
                            if (response.cotas_protegidas > 0) {
                                message += `<br><br><strong>⚠️ Cotas protegidas (já vendidas):</strong> ${response.cotas_protegidas}`;
                            }
                            
                            showAlertDelete('success', message);
                            
                            // Limpar formulário
                            $('#quantidade_excluir').val('');
                            
                            // Recarregar página após 3 segundos
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        } else {
                            showAlertDelete('error', '❌ ' + (response.error || 'Erro ao excluir cotas premiadas'));
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loading-delete').hide();
                        $('#btn-submit-delete').prop('disabled', false);
                        showAlertDelete('error', '❌ Erro na comunicação com o servidor: ' + error);
                    }
                });
            });
            
            function showAlertDelete(type, message) {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
                const alertHtml = `<div class="alert ${alertClass}">${message}</div>`;
                $('#alert-container-delete').html(alertHtml);
                $('.alert').fadeIn();
                
                // Scroll para a seção de exclusão
                $('html, body').animate({ 
                    scrollTop: $('#form-excluir-cotas').offset().top - 100 
                }, 300);
            }
        });
    </script>
</body>
</html>

