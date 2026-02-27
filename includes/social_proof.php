<?php
// Social Proof Generator - Notificações de Compras
class SocialProofGenerator {
    
    private $grupos_compras = [
        // GRUPO 1 - 50 compras
        [
            ['nome' => 'Carlos M.', 'cidade' => 'Lagarto - SE', 'quantidade' => 15, 'tempo' => 2],
            ['nome' => 'Ana Paula S.', 'cidade' => 'Aracaju - SE', 'quantidade' => 100, 'tempo' => 3],
            ['nome' => 'João Pedro L.', 'cidade' => 'Simão Dias - SE', 'quantidade' => 20, 'tempo' => 5],
            ['nome' => 'Mariana R.', 'cidade' => 'Feira de Santana - BA', 'quantidade' => 12, 'tempo' => 7],
            ['nome' => 'Roberto C.', 'cidade' => 'Recife - PE', 'quantidade' => 25, 'tempo' => 9],
            ['nome' => 'Juliana F.', 'cidade' => 'Caruaru - PE', 'quantidade' => 10, 'tempo' => 11],
            ['nome' => 'Fernando S.', 'cidade' => 'Arapiraca - AL', 'quantidade' => 30, 'tempo' => 13],
            ['nome' => 'Patricia M.', 'cidade' => 'Maceió - AL', 'quantidade' => 5, 'tempo' => 15],
            ['nome' => 'Lucas O.', 'cidade' => 'Vitória da Conquista - BA', 'quantidade' => 18, 'tempo' => 17],
            ['nome' => 'Amanda T.', 'cidade' => 'Juazeiro - BA', 'quantidade' => 22, 'tempo' => 19],
            ['nome' => 'Rodrigo A.', 'cidade' => 'Petrolina - PE', 'quantidade' => 13, 'tempo' => 21],
            ['nome' => 'Camila S.', 'cidade' => 'Garanhuns - PE', 'quantidade' => 16, 'tempo' => 23],
            ['nome' => 'Daniel P.', 'cidade' => 'Penedo - AL', 'quantidade' => 9, 'tempo' => 25],
            ['nome' => 'Leticia B.', 'cidade' => 'Palmeira dos Índios - AL', 'quantidade' => 27, 'tempo' => 27],
            ['nome' => 'Marcos V.', 'cidade' => 'Tobias Barreto - SE', 'quantidade' => 11, 'tempo' => 29],
            ['nome' => 'Isabela C.', 'cidade' => 'Capela - SE', 'quantidade' => 14, 'tempo' => 31],
            ['nome' => 'Gabriel N.', 'cidade' => 'Propriá - SE', 'quantidade' => 19, 'tempo' => 33],
            ['nome' => 'Vanessa L.', 'cidade' => 'Esplanada - BA', 'quantidade' => 24, 'tempo' => 35],
            ['nome' => 'Thiago R.', 'cidade' => 'Entre Rios - BA', 'quantidade' => 7, 'tempo' => 37],
            ['nome' => 'Rafaela M.', 'cidade' => 'Rio Real - BA', 'quantidade' => 28, 'tempo' => 39],
            ['nome' => 'Bruno H.', 'cidade' => 'Alagoinhas - BA', 'quantidade' => 17, 'tempo' => 41],
            ['nome' => 'Larissa K.', 'cidade' => 'Jequié - BA', 'quantidade' => 21, 'tempo' => 43],
            ['nome' => 'Diego F.', 'cidade' => 'Irecê - BA', 'quantidade' => 6, 'tempo' => 45],
            ['nome' => 'Aline S.', 'cidade' => 'Paulo Afonso - BA', 'quantidade' => 32, 'tempo' => 47],
            ['nome' => 'Gustavo P.', 'cidade' => 'Serrinha - BA', 'quantidade' => 14, 'tempo' => 49],
            ['nome' => 'Bianca R.', 'cidade' => 'São Miguel dos Campos - AL', 'quantidade' => 23, 'tempo' => 51],
            ['nome' => 'Rafael T.', 'cidade' => 'União dos Palmares - AL', 'quantidade' => 10, 'tempo' => 53],
            ['nome' => 'Tatiana G.', 'cidade' => 'Coruripe - AL', 'quantidade' => 26, 'tempo' => 55],
            ['nome' => 'Leandro M.', 'cidade' => 'Rio Largo - AL', 'quantidade' => 8, 'tempo' => 57],
            ['nome' => 'Priscila O.', 'cidade' => 'Bom Conselho - PE', 'quantidade' => 29, 'tempo' => 59],
            ['nome' => 'Felipe A.', 'cidade' => 'Lajedo - PE', 'quantidade' => 12, 'tempo' => 4],
            ['nome' => 'Natalia B.', 'cidade' => 'Águas Belas - PE', 'quantidade' => 35, 'tempo' => 6],
            ['nome' => 'Vinicius C.', 'cidade' => 'Arcoverde - PE', 'quantidade' => 16, 'tempo' => 8],
            ['nome' => 'Debora D.', 'cidade' => 'Santa Cruz do Capibaribe - PE', 'quantidade' => 20, 'tempo' => 10],
            ['nome' => 'Anderson E.', 'cidade' => 'Toritama - PE', 'quantidade' => 11, 'tempo' => 12],
            ['nome' => 'Fernanda G.', 'cidade' => 'Carpina - PE', 'quantidade' => 25, 'tempo' => 14],
            ['nome' => 'Mauricio H.', 'cidade' => 'Surubim - PE', 'quantidade' => 9, 'tempo' => 16],
            ['nome' => 'Carla I.', 'cidade' => 'Bezerros - PE', 'quantidade' => 33, 'tempo' => 18],
            ['nome' => 'Henrique J.', 'cidade' => 'Escada - PE', 'quantidade' => 15, 'tempo' => 20],
            ['nome' => 'Adriana K.', 'cidade' => 'Catende - PE', 'quantidade' => 22, 'tempo' => 22],
            ['nome' => 'Paulo L.', 'cidade' => 'Glória - SE', 'quantidade' => 18, 'tempo' => 24],
            ['nome' => 'Renata N.', 'cidade' => 'Ubaúba - SE', 'quantidade' => 7, 'tempo' => 26],
            ['nome' => 'Sergio O.', 'cidade' => 'Japaratuba - SE', 'quantidade' => 31, 'tempo' => 28],
            ['nome' => 'Monica P.', 'cidade' => 'Pacatuba - SE', 'quantidade' => 13, 'tempo' => 30],
            ['nome' => 'Alexandre Q.', 'cidade' => 'Carmópolis - SE', 'quantidade' => 24, 'tempo' => 32],
            ['nome' => 'Elaine R.', 'cidade' => 'Campo do Brito - SE', 'quantidade' => 17, 'tempo' => 34],
            ['nome' => 'Ricardo S.', 'cidade' => 'Ribeirópolis - SE', 'quantidade' => 28, 'tempo' => 36],
            ['nome' => 'Silvia T.', 'cidade' => 'Tomar do Geru - SE', 'quantidade' => 10, 'tempo' => 38],
            ['nome' => 'Wagner U.', 'cidade' => 'São Sebastião - BA', 'quantidade' => 36, 'tempo' => 40],
            ['nome' => 'Daniela V.', 'cidade' => 'Santo Estevão - BA', 'quantidade' => 19, 'tempo' => 42],
        ],
        
        // GRUPO 2 - 50 compras
        [
            ['nome' => 'Eduardo A.', 'cidade' => 'Ruy Barbosa - BA', 'quantidade' => 21, 'tempo' => 3],
            ['nome' => 'Claudia B.', 'cidade' => 'Aracaju - SE', 'quantidade' => 17, 'tempo' => 5],
            ['nome' => 'Marcelo C.', 'cidade' => 'Bom Jesus da Lapa - BA', 'quantidade' => 100, 'tempo' => 7],
            ['nome' => 'Bruna D.', 'cidade' => 'Morro do Chapéu - BA', 'quantidade' => 23, 'tempo' => 9],
            ['nome' => 'André E.', 'cidade' => 'Xique-Xique - BA', 'quantidade' => 15, 'tempo' => 11],
            ['nome' => 'Tatiana F.', 'cidade' => 'Seabra - BA', 'quantidade' => 12, 'tempo' => 13],
            ['nome' => 'Vinicius G.', 'cidade' => 'Ubaitaba - BA', 'quantidade' => 28, 'tempo' => 15],
            ['nome' => 'Gabriela H.', 'cidade' => 'Miguel Calmon - BA', 'quantidade' => 8, 'tempo' => 17],
            ['nome' => 'Felipe I.', 'cidade' => 'Ribeira do Pombal - BA', 'quantidade' => 20, 'tempo' => 19],
            ['nome' => 'Priscila J.', 'cidade' => 'Tucano - BA', 'quantidade' => 26, 'tempo' => 21],
            ['nome' => 'Leonardo K.', 'cidade' => 'Euclides da Cunha - BA', 'quantidade' => 11, 'tempo' => 23],
            ['nome' => 'Simone L.', 'cidade' => 'Teotônio Vilela - AL', 'quantidade' => 34, 'tempo' => 25],
            ['nome' => 'Cristiano M.', 'cidade' => 'Campo Alegre - AL', 'quantidade' => 14, 'tempo' => 27],
            ['nome' => 'Jéssica N.', 'cidade' => 'Junqueiro - AL', 'quantidade' => 19, 'tempo' => 29],
            ['nome' => 'Fábio O.', 'cidade' => 'Boca da Mata - AL', 'quantidade' => 24, 'tempo' => 31],
            ['nome' => 'Luciana P.', 'cidade' => 'Pilar - AL', 'quantidade' => 9, 'tempo' => 33],
            ['nome' => 'Júlio Q.', 'cidade' => 'Craíbas - AL', 'quantidade' => 30, 'tempo' => 35],
            ['nome' => 'Raquel R.', 'cidade' => 'Murici - AL', 'quantidade' => 16, 'tempo' => 37],
            ['nome' => 'Marcio S.', 'cidade' => 'Ouro Branco - PE', 'quantidade' => 22, 'tempo' => 39],
            ['nome' => 'Viviane T.', 'cidade' => 'Ibimirim - PE', 'quantidade' => 13, 'tempo' => 41],
            ['nome' => 'Robson U.', 'cidade' => 'Pão de Açúcar - PE', 'quantidade' => 27, 'tempo' => 43],
            ['nome' => 'Karla V.', 'cidade' => 'Agrestina - PE', 'quantidade' => 18, 'tempo' => 45],
            ['nome' => 'Rogério W.', 'cidade' => 'Paudalho - PE', 'quantidade' => 7, 'tempo' => 47],
            ['nome' => 'Solange X.', 'cidade' => 'Flexeiras - PE', 'quantidade' => 32, 'tempo' => 49],
            ['nome' => 'Valter Y.', 'cidade' => 'São Luiz do Quitunde - PE', 'quantidade' => 10, 'tempo' => 51],
            ['nome' => 'Tânia Z.', 'cidade' => 'Lagarto - SE', 'quantidade' => 25, 'tempo' => 53],
            ['nome' => 'Milton A.', 'cidade' => 'Simão Dias - SE', 'quantidade' => 15, 'tempo' => 55],
            ['nome' => 'Cristina B.', 'cidade' => 'Feira de Santana - BA', 'quantidade' => 29, 'tempo' => 57],
            ['nome' => 'Oswaldo C.', 'cidade' => 'Recife - PE', 'quantidade' => 12, 'tempo' => 59],
            ['nome' => 'Sabrina D.', 'cidade' => 'Caruaru - PE', 'quantidade' => 35, 'tempo' => 4],
            ['nome' => 'Denis E.', 'cidade' => 'Arapiraca - AL', 'quantidade' => 20, 'tempo' => 6],
            ['nome' => 'Alessandra F.', 'cidade' => 'Maceió - AL', 'quantidade' => 8, 'tempo' => 8],
            ['nome' => 'César G.', 'cidade' => 'Vitória da Conquista - BA', 'quantidade' => 31, 'tempo' => 10],
            ['nome' => 'Eliana H.', 'cidade' => 'Juazeiro - BA', 'quantidade' => 14, 'tempo' => 12],
            ['nome' => 'Gilberto I.', 'cidade' => 'Petrolina - PE', 'quantidade' => 23, 'tempo' => 14],
            ['nome' => 'Helena J.', 'cidade' => 'Garanhuns - PE', 'quantidade' => 17, 'tempo' => 16],
            ['nome' => 'Ivan K.', 'cidade' => 'Penedo - AL', 'quantidade' => 28, 'tempo' => 18],
            ['nome' => 'Joana L.', 'cidade' => 'Palmeira dos Índios - AL', 'quantidade' => 11, 'tempo' => 20],
            ['nome' => 'Klaus M.', 'cidade' => 'Tobias Barreto - SE', 'quantidade' => 26, 'tempo' => 22],
            ['nome' => 'Lúcia N.', 'cidade' => 'Capela - SE', 'quantidade' => 9, 'tempo' => 24],
            ['nome' => 'Mário O.', 'cidade' => 'Propriá - SE', 'quantidade' => 33, 'tempo' => 26],
            ['nome' => 'Norma P.', 'cidade' => 'Esplanada - BA', 'quantidade' => 16, 'tempo' => 28],
            ['nome' => 'Otávio Q.', 'cidade' => 'Entre Rios - BA', 'quantidade' => 21, 'tempo' => 30],
            ['nome' => 'Paula R.', 'cidade' => 'Rio Real - BA', 'quantidade' => 13, 'tempo' => 32],
            ['nome' => 'Quintino S.', 'cidade' => 'Alagoinhas - BA', 'quantidade' => 24, 'tempo' => 34],
            ['nome' => 'Rosa T.', 'cidade' => 'Jequié - BA', 'quantidade' => 19, 'tempo' => 36],
            ['nome' => 'Samuel U.', 'cidade' => 'Irecê - BA', 'quantidade' => 27, 'tempo' => 38],
            ['nome' => 'Teresa V.', 'cidade' => 'Paulo Afonso - BA', 'quantidade' => 10, 'tempo' => 40],
            ['nome' => 'Ulisses W.', 'cidade' => 'Serrinha - BA', 'quantidade' => 36, 'tempo' => 42],
            ['nome' => 'Vera X.', 'cidade' => 'São Miguel dos Campos - AL', 'quantidade' => 15, 'tempo' => 44],
        ],
        
        // GRUPO 3 - 50 compras
        [
            ['nome' => 'Alberto A.', 'cidade' => 'União dos Palmares - AL', 'quantidade' => 18, 'tempo' => 2],
            ['nome' => 'Beatriz B.', 'cidade' => 'Coruripe - AL', 'quantidade' => 22, 'tempo' => 4],
            ['nome' => 'Caio C.', 'cidade' => 'Rio Largo - AL', 'quantidade' => 100, 'tempo' => 6],
            ['nome' => 'Daniele D.', 'cidade' => 'Bom Conselho - PE', 'quantidade' => 29, 'tempo' => 8],
            ['nome' => 'Emerson E.', 'cidade' => 'Lajedo - PE', 'quantidade' => 11, 'tempo' => 10],
            ['nome' => 'Fabiana F.', 'cidade' => 'Águas Belas - PE', 'quantidade' => 25, 'tempo' => 12],
            ['nome' => 'Guilherme G.', 'cidade' => 'Arcoverde - PE', 'quantidade' => 17, 'tempo' => 14],
            ['nome' => 'Heloisa H.', 'cidade' => 'Santa Cruz do Capibaribe - PE', 'quantidade' => 7, 'tempo' => 16],
            ['nome' => 'Ícaro I.', 'cidade' => 'Toritama - PE', 'quantidade' => 31, 'tempo' => 18],
            ['nome' => 'Jaqueline J.', 'cidade' => 'Carpina - PE', 'quantidade' => 13, 'tempo' => 20],
            ['nome' => 'Kleber K.', 'cidade' => 'Surubim - PE', 'quantidade' => 24, 'tempo' => 22],
            ['nome' => 'Livia L.', 'cidade' => 'Bezerros - PE', 'quantidade' => 9, 'tempo' => 24],
            ['nome' => 'Mateus M.', 'cidade' => 'Escada - PE', 'quantidade' => 33, 'tempo' => 26],
            ['nome' => 'Natasha N.', 'cidade' => 'Catende - PE', 'quantidade' => 16, 'tempo' => 28],
            ['nome' => 'Orlando O.', 'cidade' => 'Glória - SE', 'quantidade' => 20, 'tempo' => 30],
            ['nome' => 'Patrícia P.', 'cidade' => 'Ubaúba - SE', 'quantidade' => 12, 'tempo' => 32],
            ['nome' => 'Quirino Q.', 'cidade' => 'Japaratuba - SE', 'quantidade' => 27, 'tempo' => 34],
            ['nome' => 'Renato R.', 'cidade' => 'Pacatuba - SE', 'quantidade' => 8, 'tempo' => 36],
            ['nome' => 'Sandra S.', 'cidade' => 'Carmópolis - SE', 'quantidade' => 35, 'tempo' => 38],
            ['nome' => 'Túlio T.', 'cidade' => 'Campo do Brito - SE', 'quantidade' => 19, 'tempo' => 40],
            ['nome' => 'Ursula U.', 'cidade' => 'Ribeirópolis - SE', 'quantidade' => 23, 'tempo' => 42],
            ['nome' => 'Valdemar V.', 'cidade' => 'Tomar do Geru - SE', 'quantidade' => 15, 'tempo' => 44],
            ['nome' => 'Wanda W.', 'cidade' => 'São Sebastião - BA', 'quantidade' => 28, 'tempo' => 46],
            ['nome' => 'Xavier X.', 'cidade' => 'Santo Estevão - BA', 'quantidade' => 10, 'tempo' => 48],
            ['nome' => 'Yara Y.', 'cidade' => 'Ruy Barbosa - BA', 'quantidade' => 32, 'tempo' => 50],
            ['nome' => 'Zuleica Z.', 'cidade' => 'Aracaju - SE', 'quantidade' => 14, 'tempo' => 52],
            ['nome' => 'Adriano A.', 'cidade' => 'Bom Jesus da Lapa - BA', 'quantidade' => 26, 'tempo' => 54],
            ['nome' => 'Bárbara B.', 'cidade' => 'Morro do Chapéu - BA', 'quantidade' => 18, 'tempo' => 56],
            ['nome' => 'Cláudio C.', 'cidade' => 'Xique-Xique - BA', 'quantidade' => 21, 'tempo' => 58],
            ['nome' => 'Denise D.', 'cidade' => 'Seabra - BA', 'quantidade' => 9, 'tempo' => 3],
            ['nome' => 'Edson E.', 'cidade' => 'Ubaitaba - BA', 'quantidade' => 30, 'tempo' => 5],
            ['nome' => 'Fátima F.', 'cidade' => 'Miguel Calmon - BA', 'quantidade' => 13, 'tempo' => 7],
            ['nome' => 'Geraldo G.', 'cidade' => 'Ribeira do Pombal - BA', 'quantidade' => 24, 'tempo' => 9],
            ['nome' => 'Hilda H.', 'cidade' => 'Tucano - BA', 'quantidade' => 17, 'tempo' => 11],
            ['nome' => 'Ivo I.', 'cidade' => 'Euclides da Cunha - BA', 'quantidade' => 29, 'tempo' => 13],
            ['nome' => 'Joice J.', 'cidade' => 'Teotônio Vilela - AL', 'quantidade' => 11, 'tempo' => 15],
            ['nome' => 'Karina K.', 'cidade' => 'Campo Alegre - AL', 'quantidade' => 25, 'tempo' => 17],
            ['nome' => 'Lauro L.', 'cidade' => 'Junqueiro - AL', 'quantidade' => 16, 'tempo' => 19],
            ['nome' => 'Marta M.', 'cidade' => 'Boca da Mata - AL', 'quantidade' => 22, 'tempo' => 21],
            ['nome' => 'Nelson N.', 'cidade' => 'Pilar - AL', 'quantidade' => 8, 'tempo' => 23],
            ['nome' => 'Olga O.', 'cidade' => 'Craíbas - AL', 'quantidade' => 34, 'tempo' => 25],
            ['nome' => 'Pedro P.', 'cidade' => 'Murici - AL', 'quantidade' => 12, 'tempo' => 27],
            ['nome' => 'Quitéria Q.', 'cidade' => 'Ouro Branco - PE', 'quantidade' => 27, 'tempo' => 29],
            ['nome' => 'Raimundo R.', 'cidade' => 'Ibimirim - PE', 'quantidade' => 19, 'tempo' => 31],
            ['nome' => 'Sônia S.', 'cidade' => 'Pão de Açúcar - PE', 'quantidade' => 15, 'tempo' => 33],
            ['nome' => 'Tiago T.', 'cidade' => 'Agrestina - PE', 'quantidade' => 28, 'tempo' => 35],
            ['nome' => 'Úrsula U.', 'cidade' => 'Paudalho - PE', 'quantidade' => 10, 'tempo' => 37],
            ['nome' => 'Vilma V.', 'cidade' => 'Flexeiras - PE', 'quantidade' => 31, 'tempo' => 39],
            ['nome' => 'Wilson W.', 'cidade' => 'São Luiz do Quitunde - PE', 'quantidade' => 20, 'tempo' => 41],
            ['nome' => 'Yolanda X.', 'cidade' => 'Recife - PE', 'quantidade' => 23, 'tempo' => 43],
        ]
    ];
    
    public function getGrupoAleatorio() {
        $indice_grupo = array_rand($this->grupos_compras);
        return $this->grupos_compras[$indice_grupo];
    }
    
    public function gerarHTML() {
        $compras = $this->getGrupoAleatorio();
        $compras_json = json_encode($compras);
        
        // CSS inline (ofuscado)
        $css = $this->getCSS();
        
        // JavaScript inline (ofuscado)
        $js = $this->getJS($compras_json);
        
        return $css . $js;
    }
    
    private function getCSS() {
        return <<<'CSS'
<style>
.spn{position:fixed;top:20px;left:20px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.3),0 0 0 1px rgba(255,255,255,.1);padding:18px 20px;display:flex;align-items:center;gap:14px;max-width:min(380px,60vw);z-index:99999;transform:translateX(-450px);opacity:0;transition:all .4s cubic-bezier(.68,-.55,.265,1.55);border:2px solid rgba(255,255,255,.2)}
.spn.active{transform:translateX(0);opacity:1;animation:spnPulse .6s ease-in-out}
@keyframes spnPulse{0%{transform:translateX(0) scale(1)}50%{transform:translateX(0) scale(1.02)}100%{transform:translateX(0) scale(1)}}
.spn-i{font-size:32px;flex-shrink:0;animation:spb 1.2s ease-in-out infinite;filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))}
@keyframes spb{0%,100%{transform:translateY(0) rotate(0deg)}25%{transform:translateY(-6px) rotate(-5deg)}75%{transform:translateY(-6px) rotate(5deg)}}
.spn-c{flex:1;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}
.spn-n{font-weight:700;color:#fff;font-size:15px;margin-bottom:4px;text-shadow:0 1px 3px rgba(0,0,0,.3)}
.spn-t{color:rgba(255,255,255,.95);font-size:14px;margin-bottom:3px;font-weight:500;line-height:1.4}
.spn-t strong{color:#ffd700;font-weight:700;text-shadow:0 1px 2px rgba(0,0,0,.2)}
.spn-tm{color:rgba(255,255,255,.75);font-size:12px;font-weight:500}
.spn-x{position:absolute;top:6px;right:6px;background:rgba(255,255,255,.15);border:none;font-size:22px;color:#fff;cursor:pointer;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:all .2s;backdrop-filter:blur(4px)}
.spn-x:hover{background:rgba(255,255,255,.3);transform:rotate(90deg)}
@media (max-width:768px){.spn{left:10px;right:10px;max-width:calc(100% - 20px);top:10px}}
</style>
CSS;
    }
    
    private function getJS($compras_json) {
        // Gera nomes de variáveis aleatórios para ofuscar
        $var1 = 'd' . substr(md5(rand()), 0, 6);
        $var2 = 'n' . substr(md5(rand()), 0, 6);
        $var3 = 'i' . substr(md5(rand()), 0, 6);
        
        return <<<JS
<script>
(function(){
var {$var1}={$compras_json};
var {$var2}=0;
function {$var3}(){
if({$var1}.length===0)return;
var c={$var1}[{$var2}];
var n=document.createElement('div');
n.className='spn';
n.innerHTML='<div class="spn-i">🎉</div><div class="spn-c"><div class="spn-n">'+c.nome+'</div><div class="spn-t">de <strong>'+c.cidade+'</strong> comprou <strong>'+c.quantidade+' COTAS</strong></div><div class="spn-tm">há '+c.tempo+' minutos</div></div><button class="spn-x" onclick="this.parentElement.remove()">×</button>';
document.body.appendChild(n);
setTimeout(function(){n.classList.add('active')},100);
setTimeout(function(){n.classList.remove('active');setTimeout(function(){n.remove()},300)},5000);
{$var2}=({$var2}+1)%{$var1}.length;
}
setTimeout(function(){{$var3}();setInterval({$var3},Math.random()*8000+12000)},6000);
})();
</script>
JS;
    }
}
?>
