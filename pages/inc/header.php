<?php


$user_id = $_settings->userdata('id');
$user_type = $_settings->userdata('type');
$logo = validate_image($_settings->info('logo'));
$favicon = validate_image($_settings->info('favicon'));
$enable_password = $_settings->info('enable_password');
$enable_pixel = $_settings->info('enable_pixel');
$enable_ga4 = $_settings->info('enable_ga4');
$google_ga4_id = $_settings->info('google_ga4_id');
$enable_gtm = $_settings->info('enable_gtm');
$google_gtm_id = $_settings->info('google_gtm_id');
$facebook_access_token = $_settings->info('facebook_access_token');
$facebook_pixel_id = $_settings->info('facebook_pixel_id');
$affiliate = $_settings->userdata('is_affiliate');

// Dados resumidos do afiliado (só busca se for afiliado logado)
$aff_referral_code = '';
$aff_pending       = 0;
$aff_paid          = 0;
$aff_sales_count   = 0;
if ($user_id && $affiliate == 1) {
    $qryAffHdr = $conn->query("SELECT referral_code, amount_pending, amount_paid FROM referral WHERE customer_id = '$user_id' LIMIT 1");
    if ($qryAffHdr && $qryAffHdr->num_rows > 0) {
        $rowAffHdr         = $qryAffHdr->fetch_assoc();
        $aff_referral_code = $rowAffHdr['referral_code'];
        $aff_pending       = $rowAffHdr['amount_pending'];
        $aff_paid          = $rowAffHdr['amount_paid'];
        $qryAffCnt = $conn->query("SELECT COUNT(id) as total FROM order_list WHERE referral_id = '$aff_referral_code'");
        if ($qryAffCnt && $qryAffCnt->num_rows > 0) {
            $aff_sales_count = $qryAffCnt->fetch_assoc()['total'];
        }
    }
}
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$parts = parse_url($url);
$path_name = $parts['path'];
$path = explode('/', $path_name);
$page = $path[1];

if (isset($parts['query'])) {
    parse_str($parts['query'], $query);
    $ref = $query['ref'];

    if (isset($ref)) {
        $_SESSION['ref'] = $ref;
    }
}
?>
<html translate="no">
<html lang="pt-br">
<head>
   <meta charset="utf-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no">
   <?php echo exibir_cabecalho($conn); ?>

   <?php if ($favicon): ?>
      <link rel="shortcut icon" href="<?php echo $favicon; ?>" />
      <link rel="apple-touch-icon" sizes="180x180" href="<?php echo validate_image($_settings->info('favicon')); ?>">
      <link rel="icon" type="image/png" sizes="32x32" href="<?php echo validate_image($_settings->info('favicon')); ?>">
      <link rel="icon" type="image/png" sizes="16x16" href="<?php echo validate_image($_settings->info('favicon')); ?>">
   <?php endif; ?>
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
   <meta name="theme-color" content="#000000">
   <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
   <script src="<?php echo BASE_URL; ?>includes/jquery/jquery.min.js"></script>
   <script> var _base_url_ = '<?php echo BASE_URL; ?>'; </script>

   <?php if (($enable_pixel == 1) && !empty($facebook_pixel_id)): ?>
      <script>
         !function(f, b, e, v, n, t, s) {
            if (f.fbq) return; 
            n = f.fbq = function() {
               n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n; 
            n.push = n; 
            n.loaded = !0; 
            n.version = '2.0';
            n.queue = []; 
            t = b.createElement(e); 
            t.async = !0;
            t.src = v; 
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
         }(window, document, 'script','https://connect.facebook.net/en_US/fbevents.js');
         fbq('init', '<?php echo $facebook_pixel_id; ?>');
         fbq('track', 'PageView');
      </script>
      <noscript>
         <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo $facebook_pixel_id; ?>&ev=PageView&noscript=1" />
      </noscript>
   <?php endif; ?>

   <?php if (($enable_ga4 == 1) && !empty($google_ga4_id)): ?>
      <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $google_ga4_id; ?>"></script>
      <script>
         window.dataLayer = window.dataLayer || [];
         function gtag(){dataLayer.push(arguments);}
         gtag('js', new Date());
         gtag('config', '<?php echo $google_ga4_id; ?>');
      </script>
   <?php endif; ?>

   <?php if (($enable_gtm == 1) && !empty($google_gtm_id)): ?>
      <!-- Google Tag Manager -->
      <script>
         (function(w,d,s,l,i){
            w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});
            var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
            j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
            f.parentNode.insertBefore(j,f);
         })(window,document,'script','dataLayer','<?php echo $google_gtm_id; ?>');
      </script>
      <!-- End Google Tag Manager -->
   <?php endif; ?>
</head>
<body>
<?php if (($enable_gtm == 1) && !empty($google_gtm_id)): ?>
   <!-- Google Tag Manager (noscript) -->
   <noscript>
      <iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $google_gtm_id; ?>"
      height="0" width="0" style="display:none;visibility:hidden"></iframe>
   </noscript>
   <!-- Ending Google Tag Manager (noscript) -->
<?php endif; ?>

<div id="__next">
   <header class="header-app-header <?php echo $page; ?>">
      <div class="header-app-header-container">
         <div class="container container-600 font-mdd">
            <div style="text-align-last: justify; padding: 20 0 20 0;">
               
               
                <button type="button" aria-label="Menu" class="btn btn-link text-white font-lgg ps-0" data-bs-toggle="modal" data-bs-target="#mobileMenu" style="margin-top:5px">
                    <i class="bi bi-filter-left"></i>
                </button>
                <a class="flex-grow-1 text-center" href="/">
                <?php if ($logo): ?>
                   <img src="<?php echo $logo; ?>" class="header-app-brand">
                <?php else: ?>
                   <img src="<?php echo BASE_URL; ?>assets/img/logo.png" class="header-app-brand">
                <?php endif; ?>
                </a>
                 <?php if (CONTACT_TYPE == '1'): ?>
                    <a class="btn btn-link text-white pe-0 text-right text-decoration-none" href="/contato">
                <?php else: ?>
                    <a class="btn btn-link text-white pe-0 text-right text-decoration-none" href="https://api.whatsapp.com/send/?phone=55<?php echo $_settings->info('phone'); ?>">
                <?php endif; ?>
                   <div class="duvida d-flex justify-content-end opacity-50"><i class="bi bi-chat-dots-fill"></i></div>
                   <div class="duvida text-yellow font-xss">Suporte!</div>
                </a> 
            </div>
         </div>
      </div>
   </header>
   <div class="black-bar <?php echo $page; ?> fuse"></div>
   <menu id="mobileMenu" class="modal fade modal-fluid" tabindex="-1" aria-labelledby="mobileMenuLabel" aria-hidden="true">
      <div class="modal-dialog modal-fullscreen">
         <div class="modal-content bg-cor-primaria">
            <header class="app-header app-header-mobile--show">
               <div class="container container-600 h-100 d-flex align-items-center justify-content-between">
                  <?php if ($logo): ?>
                     <a href="/"><img src="<?php echo $logo; ?>" class="app-brand img-fluid"></a>
                  <?php else: ?>
                     <a href="/"><img src="<?php echo BASE_URL; ?>assets/img/logo.png" class="app-brand img-fluid"></a>
                  <?php endif; ?>
                  <div class="app-header-mobile">
                     <button type="button" class="btn btn-link text-white menu-mobile--button pe-0 font-lgg" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="bi bi-x-circle"></i>
                     </button>
                  </div>
               </div>
            </header>
            <div class="modal-body">
               <div class="container container-600">
                  <?php if ($user_id): ?>
                     <div class="card-usuario mb-2">
                        <picture>
                           <img src="<?php echo ($_settings->userdata('avatar') ? validate_image($_settings->userdata('avatar')) : BASE_URL . 'assets/img/avatar.png'); ?>" class="img-fluid img-perfil">
                        </picture>
                        <div class="card-usuario--informacoes">
                           <h3>Olá, <?php echo ucwords($_settings->userdata('firstname')) . ' ' . ucwords($_settings->userdata('lastname')); ?></h3>
                           <div class="email font-xss saldo-value"></div>
                        </div>
                        <div class="card-usuario--sair">
                           <a href="<?php echo BASE_URL . 'logout?' . $_SERVER['REQUEST_URI']; ?>">
                              <button type="button" class="btn btn-link text-center text-white-50 ps-1 pe-0 pt-0 pb-0 font-lg">
                                 <i class="bi bi-box-arrow-left"></i>
                              </button>
                           </a>
                        </div>
                     </div>
                  <?php endif; ?>                   <nav class="nav-vertical nav-submenu font-xs mb-2">
                      <ul>
                         <li><a class="text-white" alt="Página Principal" href="/"><i class="icone bi bi-house"></i><span>Início</span></a></li>
                         <li><a class="text-white" alt="Campanhas" href="/campanhas"><i class="icone bi bi-megaphone"></i><span>Campanhas</span></a></li>
                         <li><a class="text-white" alt="Meus Números" href="/meus-numeros"><i class="icone bi bi-card-list"></i><span>Meus números Novo</span></a></li>
                         
                         <?php if ($user_id): ?>
                            <?php if ($affiliate == 1): ?>
                               <li><a class="text-white" alt="Área do Afiliado" href="/painel-de-afiliados/"><i class="icone bi bi-share"></i><span>Afiliados</span></a></li>
                            <?php else: ?>
                               <li><a class="text-white" alt="Quero ser Afiliado" href="/painel-de-afiliados/"><i class="icone bi bi-person-plus-fill"></i><span>Quero ser Afiliado</span></a></li>
                            <?php endif; ?>
                            <!-- <li><a alt="Atualizar cadastro" class="text-white" href="/perfil"><i class="icone bi bi-person"></i><span>Perfil</span></a></li> -->
                         <?php else: ?>
                            <li><a alt="Cadastrar" class="text-white" href="/cadastrar"><i class="icone bi bi-person-plus"></i><span>Cadastrar</span></a></li>
                            <!-- <li><a alt="Entrar" class="text-white" href="/login"><i class="icone bi bi-person-circle"></i><span>Entrar</span></a></li> -->
                         <?php endif; ?>
                         
                         <li><a href="<?php echo BASE_URL; ?>contato" class="text-white"><i class="icone bi bi-chat-dots-fill"></i><span>Fale Conosco</span></a></li>
                         <li><a href="<?php echo BASE_URL . 'logout?' . $_SERVER['REQUEST_URI']; ?>" class="text-white"><i class="icone bi bi-box-arrow-right"></i><span>Sair</span></a></li>
                      </ul>
                   </nav>

                   <!-- ===== CARD AFILIADO ===== -->
                   <style>
                   .aff-menu-card {
                       border-radius: 18px;
                       overflow: hidden;
                       margin-bottom: 16px;
                   }
                   /* Estado: afiliado logado */
                   .aff-card-active {
                       background: radial-gradient(103.03% 103.03% at 0% 0%, #d080ff 0%, #6c5dd3 100%);
                       padding: 18px;
                       color: #fff;
                   }
                   .aff-card-active .aff-card-title {
                       font-size: 11px;
                       font-weight: 700;
                       letter-spacing: .06em;
                       text-transform: uppercase;
                       opacity: .8;
                       margin-bottom: 4px;
                   }
                   .aff-card-stats {
                       display: flex;
                       gap: 10px;
                       margin: 10px 0 14px;
                   }
                   .aff-card-stat {
                       flex: 1;
                       background: rgba(255,255,255,.15);
                       border-radius: 10px;
                       padding: 8px 10px;
                       text-align: center;
                   }
                   .aff-card-stat label {
                       display: block;
                       font-size: 9px;
                       font-weight: 600;
                       opacity: .75;
                       text-transform: uppercase;
                       margin-bottom: 2px;
                   }
                   .aff-card-stat span {
                       font-size: 14px;
                       font-weight: 700;
                   }
                   .aff-link-row {
                       display: flex;
                       align-items: center;
                       background: rgba(255,255,255,.15);
                       border-radius: 10px;
                       overflow: hidden;
                       margin-bottom: 10px;
                   }
                   .aff-link-row input {
                       flex: 1;
                       background: transparent;
                       border: none;
                       padding: 8px 10px;
                       font-size: 11px;
                       color: #fff;
                       font-weight: 600;
                       outline: none;
                       min-width: 0;
                   }
                   .aff-link-copy-btn {
                       background: #fff;
                       border: none;
                       padding: 8px 12px;
                       color: #6c5dd3;
                       font-size: 12px;
                       font-weight: 700;
                       cursor: pointer;
                       white-space: nowrap;
                       transition: background .2s;
                   }
                   .aff-link-copy-btn:active { background: #ede9ff; }
                   .btn-aff-full {
                       display: block;
                       text-align: center;
                       background: rgba(255,255,255,.18);
                       color: #fff;
                       border-radius: 12px;
                       padding: 9px;
                       font-size: 13px;
                       font-weight: 700;
                       text-decoration: none;
                       transition: background .2s;
                   }
                   .btn-aff-full:hover { background: rgba(255,255,255,.28); color:#fff; }

                   /* Estado: logado mas não-afiliado */
                   .aff-card-join {
                       background: rgba(108,93,211,.18);
                       border: 1px solid rgba(108,93,211,.4);
                       padding: 16px;
                       text-align: center;
                   }
                   .aff-card-join p {
                       font-size: 12px;
                       color: #ccc;
                       margin: 0 0 12px;
                       line-height: 1.5;
                   }
                   .btn-join-aff {
                       display: block;
                       background: linear-gradient(135deg, #d080ff 0%, #6c5dd3 100%);
                       color: #fff;
                       border-radius: 12px;
                       padding: 11px;
                       font-size: 14px;
                       font-weight: 700;
                       text-decoration: none;
                       transition: opacity .2s;
                   }
                   .btn-join-aff:hover { opacity: .87; color:#fff; }

                   /* Estado: deslogado */
                   .aff-card-guest {
                       background: rgba(255,255,255,.07);
                       border: 1px solid rgba(255,255,255,.12);
                       padding: 16px;
                   }
                   .aff-card-guest p {
                       font-size: 12px;
                       color: #ccc;
                       margin: 0 0 12px;
                       line-height: 1.5;
                       text-align: center;
                   }
                   .aff-guest-btns { display: flex; gap: 8px; }
                   .btn-aff-login {
                       flex: 1;
                       text-align: center;
                       background: rgba(255,255,255,.12);
                       color: #fff;
                       border-radius: 10px;
                       padding: 10px 6px;
                       font-size: 12px;
                       font-weight: 700;
                       text-decoration: none;
                       border: 1px solid rgba(255,255,255,.2);
                       transition: background .2s;
                   }
                   .btn-aff-login:hover { background: rgba(255,255,255,.22); color:#fff; }
                   .btn-aff-register {
                       flex: 1;
                       text-align: center;
                       background: linear-gradient(135deg, #d080ff 0%, #6c5dd3 100%);
                       color: #fff;
                       border-radius: 10px;
                       padding: 10px 6px;
                       font-size: 12px;
                       font-weight: 700;
                       text-decoration: none;
                       transition: opacity .2s;
                   }
                   .btn-aff-register:hover { opacity: .87; color:#fff; }
                   </style>

                   <div class="aff-menu-card">
                   <?php if ($user_id && $affiliate == 1): ?>
                       <!-- AFILIADO LOGADO -->
                       <div class="aff-card-active">
                           <div class="aff-card-title">🤝 Programa de Afiliados</div>
                           <div class="aff-card-stats">
                               <div class="aff-card-stat">
                                   <label>Saldo</label>
                                   <span>R$<?= number_format($aff_pending, 2, ',', '.') ?></span>
                               </div>
                               <div class="aff-card-stat">
                                   <label>Retirado</label>
                                   <span>R$<?= number_format($aff_paid, 2, ',', '.') ?></span>
                               </div>
                               <div class="aff-card-stat">
                                   <label>Vendas</label>
                                   <span><?= $aff_sales_count ?></span>
                               </div>
                           </div>
                           <div class="aff-link-row">
                               <input id="hdr-aff-link" type="text" readonly
                                      value="<?= htmlspecialchars(BASE_REF . '?&ref=' . $aff_referral_code) ?>">
                               <button class="aff-link-copy-btn" onclick="copyHdrAffLink()">📋 Copiar</button>
                           </div>
                           <a href="/painel-de-afiliados/" class="btn-aff-full">Ver painel completo →</a>
                       </div>

                   <?php elseif ($user_id && $affiliate != 1): ?>
                       <!-- LOGADO, NÃO É AFILIADO -->
                       <div class="aff-card-join">
                           <p>💰 Ganhe comissão por cada venda indicada. Cadastre-se como afiliado!</p>
                           <a href="/painel-de-afiliados/" class="btn-join-aff">🚀 Quero ser Afiliado</a>
                       </div>

                   <?php else: ?>
                       <!-- DESLOGADO -->
                       <div class="aff-card-guest">
                           <p>💰 Já é afiliado? Entre para ver seu painel. Ou cadastre-se gratuitamente!</p>
                           <div class="aff-guest-btns">
                               <a href="/cadastrar" class="btn-aff-login">👤 Login</a>
                               <a href="/painel-de-afiliados/" class="btn-aff-register">🚀 Ser Afiliado</a>
                           </div>
                       </div>
                   <?php endif; ?>
                   </div>
                   <!-- ===== /CARD AFILIADO ===== -->

                </div>
             </div>
          </div>
       </div>
    </menu>
<script>
function copyHdrAffLink() {
    var input = document.getElementById('hdr-aff-link');
    if (!input) return;
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
        var btn = event.target.closest('button');
        btn.textContent = '✅ Copiado!';
        setTimeout(function() { btn.textContent = '📋 Copiar'; }, 2000);
    }).catch(function() {
        document.execCommand('copy');
    });
}
</script>