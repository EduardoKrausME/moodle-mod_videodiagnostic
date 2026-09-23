<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings em português do Brasil.
 *
 * @package   mod_videodiagnostic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addquestion'] = 'Adicionar pergunta';
$string['allowpostattempt'] = 'Permitir resposta após o estudo';
$string['allowpostattempt_help'] = 'Permite responder novamente às perguntas diagnósticas depois da liberação do conteúdo de estudo.';
$string['alreadysubmitted'] = 'Esta etapa diagnóstica já foi enviada.';
$string['answer'] = 'Resposta';
$string['attemptstage'] = 'Etapa';
$string['change'] = 'Mudança';
$string['changed'] = 'Mudou';
$string['changedanswers'] = 'Respostas alteradas';
$string['comparison'] = 'Comparação inicial × pós-estudo';
$string['completed'] = 'Concluído';
$string['completiondiagnostic'] = 'O estudante deve enviar o diagnóstico inicial';
$string['completiondiagnostic_desc'] = 'Enviar a resposta diagnóstica inicial';
$string['completionpercent'] = 'Percentual assistido obrigatório';
$string['completionpercent_desc'] = 'Assistir pelo menos {$a}% do vídeo diagnóstico';
$string['completionpercent_help'] = 'Exige este percentual assistido do vídeo diagnóstico para conclusão. Use 0 para desativar esta regra.';
$string['correctanswer'] = 'Resposta esperada/correta';
$string['correctanswer_help'] = 'Opcional. Para escolhas, informe exatamente o texto da opção correta. Para respostas abertas, a comparação automática só ocorre quando este campo está preenchido.';
$string['deletequestion'] = 'Excluir pergunta';
$string['deletequestionconfirm'] = 'Excluir esta pergunta diagnóstica? As respostas existentes para ela também serão excluídas.';
$string['details'] = 'Detalhes';
$string['diagnosticresult'] = 'Resultado diagnóstico';
$string['diagnosticsettings'] = 'Fluxo diagnóstico';
$string['editquestion'] = 'Editar pergunta';
$string['expectedend'] = 'Tempo final esperado (segundos)';
$string['expectedstart'] = 'Tempo inicial esperado (segundos)';
$string['explanationpublished'] = 'Publicar conteúdo de estudo';
$string['explanationpublished_help'] = 'Desative para manter a etapa explicativa oculta até o professor decidir liberá-la.';
$string['explanationsource'] = 'Fonte do vídeo explicativo';
$string['explanationvideofile'] = 'Arquivo do vídeo explicativo';
$string['explanationvideourl'] = 'URL do vídeo explicativo';
$string['exportcsv'] = 'Exportar CSV';
$string['final'] = 'Pós-estudo';
$string['finalresponse'] = 'Resposta pós-estudo';
$string['finalresult'] = 'Resultado pós-estudo';
$string['generalsettings'] = 'Vídeo diagnóstico';
$string['initial'] = 'Inicial';
$string['initialdiagnosis'] = 'Diagnóstico inicial';
$string['initialinstructions'] = 'Responda com base no que você sabe agora, antes de visualizar a explicação.';
$string['initialresponse'] = 'Resposta inicial';
$string['initialresult'] = 'Resultado inicial';
$string['initialsaved'] = 'Seu diagnóstico inicial foi salvo.';
$string['inprogress'] = 'Em andamento';
$string['invalidquestion'] = 'Pergunta diagnóstica inválida.';
$string['invalidstage'] = 'Etapa diagnóstica inválida.';
$string['invalidvideo'] = 'Não foi possível resolver a fonte de vídeo configurada.';
$string['lastaccess'] = 'Última atualização do tracking';
$string['lastposition'] = 'Última posição';
$string['managequestions'] = 'Gerenciar perguntas';
$string['markcurrent'] = 'Usar tempo atual do vídeo';
$string['markend'] = 'Marcar fim';
$string['markstart'] = 'Marcar início';
$string['materials'] = 'Materiais complementares';
$string['modulename'] = 'Video Diagnostic';
$string['modulename_help'] = 'Utilize uma situação em vídeo para registrar o conhecimento inicial, liberar explicações e materiais e comparar a resposta inicial com uma resposta posterior.';
$string['modulenameplural'] = 'Video Diagnostics';
$string['movedown'] = 'Mover para baixo';
$string['moveup'] = 'Mover para cima';
$string['no'] = 'Não';
$string['noactivities'] = 'Não há atividades Video Diagnostic neste curso.';
$string['noquestions'] = 'Nenhuma pergunta diagnóstica foi criada ainda.';
$string['nostudents'] = 'Nenhum estudante matriculado foi encontrado.';
$string['notstarted'] = 'Não iniciado';
$string['notsubmitted'] = 'Não enviado';
$string['options'] = 'Opções de escolha';
$string['options_help'] = 'Informe uma opção por linha.';
$string['pluginadministration'] = 'Administração do Video Diagnostic';
$string['pluginname'] = 'Video Diagnostic';
$string['postinstructions'] = 'Responda novamente depois de estudar a explicação e os materiais liberados.';
$string['postsaved'] = 'Seu diagnóstico pós-estudo foi salvo.';
$string['poststudydiagnosis'] = 'Diagnóstico pós-estudo';
$string['privacy:attemptpath'] = 'Etapa {$a}';
$string['privacy:diagnosticpath'] = 'Video Diagnostic: {$a}';
$string['privacy:metadata:videodiagnostic_attempts'] = 'Armazena as tentativas diagnósticas inicial e pós-estudo.';
$string['privacy:metadata:videodiagnostic_attempts:score'] = 'Resultado diagnóstico calculado automaticamente quando existem critérios de correção.';
$string['privacy:metadata:videodiagnostic_attempts:stage'] = 'Indica se a tentativa é inicial ou pós-estudo.';
$string['privacy:metadata:videodiagnostic_attempts:timesubmitted'] = 'Momento em que a tentativa foi enviada.';
$string['privacy:metadata:videodiagnostic_attempts:userid'] = 'Usuário que enviou a tentativa diagnóstica.';
$string['privacy:metadata:videodiagnostic_progress'] = 'Armazena o progresso de visualização do vídeo diagnóstico.';
$string['privacy:metadata:videodiagnostic_progress:lastposition'] = 'Última posição observada de reprodução.';
$string['privacy:metadata:videodiagnostic_progress:percent'] = 'Percentual de conteúdo único do vídeo assistido.';
$string['privacy:metadata:videodiagnostic_progress:userid'] = 'Usuário cujo progresso de visualização é armazenado.';
$string['privacy:metadata:videodiagnostic_progress:watchedsegments'] = 'Trechos mesclados do vídeo observados como assistidos.';
$string['privacy:metadata:videodiagnostic_responses'] = 'Armazena as respostas às perguntas diagnósticas.';
$string['privacy:metadata:videodiagnostic_responses:answertext'] = 'Resposta textual ou opção selecionada.';
$string['privacy:metadata:videodiagnostic_responses:endtime'] = 'Final do intervalo selecionado no vídeo.';
$string['privacy:metadata:videodiagnostic_responses:starttime'] = 'Momento do vídeo ou início do intervalo selecionado.';
$string['progress'] = 'Progresso';
$string['qtypechoice'] = 'Escolha';
$string['qtypeinterval'] = 'Marcar um intervalo do vídeo';
$string['qtypemarker'] = 'Marcar um momento no vídeo';
$string['qtypetext'] = 'Resposta aberta';
$string['question'] = 'Pergunta';
$string['questionadded'] = 'Pergunta adicionada.';
$string['questiondeleted'] = 'Pergunta excluída.';
$string['questiontext'] = 'Pergunta';
$string['questiontype'] = 'Tipo de pergunta';
$string['questionupdated'] = 'Pergunta atualizada.';
$string['releaseafterinitial'] = 'Liberar conteúdo de estudo somente após o diagnóstico inicial';
$string['releaseafterinitial_help'] = 'Quando ativo, o estudante precisa enviar o diagnóstico inicial antes de visualizar explicação, solução, comentários, materiais e vídeo explicativo.';
$string['report'] = 'Relatório';
$string['reports'] = 'Relatórios';
$string['required'] = 'Resposta obrigatória';
$string['reset'] = 'Redefinir';
$string['resetconfirm'] = 'Redefinir as tentativas diagnósticas e o progresso de vídeo deste estudante?';
$string['resetdone'] = 'Os dados diagnósticos do estudante foram redefinidos.';
$string['score'] = 'Resultado';
$string['scoreunavailable'] = 'Sem correção automática';
$string['seconds'] = 'segundos';
$string['selectedsegment'] = 'Momento/intervalo selecionado';
$string['solution'] = 'Solução correta / explicação';
$string['sourcenone'] = 'Sem vídeo';
$string['sourceupload'] = 'Vídeo enviado';
$string['sourceurl'] = 'URL direta de vídeo';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['status'] = 'Situação';
$string['student'] = 'Estudante';
$string['studycontentnotavailable'] = 'A etapa pós-estudo ainda não está disponível.';
$string['studylocked'] = 'Envie o diagnóstico inicial para liberar a etapa explicativa.';
$string['studymaterials'] = 'Conteúdo de estudo';
$string['studynotpublished'] = 'A etapa explicativa ainda não foi publicada pelo professor.';
$string['studysettings'] = 'Estudo e explicação';
$string['submitdiagnosis'] = 'Enviar diagnóstico';
$string['submitpostdiagnosis'] = 'Enviar resposta pós-estudo';
$string['submitted'] = 'Enviado';
$string['teachercomments'] = 'Comentários do professor';
$string['timecode'] = 'Tempo';
$string['timelinedescription'] = 'Os trechos exibidos na timeline foram efetivamente reproduzidos; regiões puladas permanecem sem marcação.';
$string['tolerance'] = 'Tolerância de tempo (segundos)';
$string['unchanged'] = 'Não mudou';
$string['videodiagnostic:addinstance'] = 'Adicionar uma atividade Video Diagnostic';
$string['videodiagnostic:exportreport'] = 'Exportar relatórios diagnósticos';
$string['videodiagnostic:managequestions'] = 'Gerenciar perguntas diagnósticas';
$string['videodiagnostic:resetresponses'] = 'Redefinir respostas diagnósticas';
$string['videodiagnostic:view'] = 'Visualizar Video Diagnostic';
$string['videodiagnostic:viewreport'] = 'Visualizar relatórios diagnósticos';
$string['videofile'] = 'Arquivo de vídeo';
$string['videosource'] = 'Fonte do vídeo';
$string['videourl'] = 'URL do vídeo';
$string['videourl_help'] = 'Informe uma URL direta de mídia, URL do YouTube ou Vimeo conforme a fonte selecionada.';
$string['viewdetails'] = 'Ver detalhes';
$string['watchprogress'] = 'Assistido';
$string['watchtimeline'] = 'Timeline de visualização';
$string['weight'] = 'Peso diagnóstico';
$string['yes'] = 'Sim';
