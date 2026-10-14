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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * Academic deadline planner language strings.
 *
 * @package local_deadlineplanner
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$string['activity'] = 'Atividade';
$string['after'] = 'Sobrecarga simulada';
$string['aftercutoff'] = 'O prazo da tarefa não pode superar a data limite de submissão.';
$string['approve'] = 'Aprovar proposta';
$string['backtodashboard'] = 'Voltar ao planejamento';
$string['before'] = 'Sobrecarga anterior';
$string['beforeopen'] = 'O prazo não pode ser anterior à data de abertura.';
$string['calendaronly'] = 'Calendário nativo (informativo)';
$string['calendartitle'] = 'Prazos acadêmicos previstos';
$string['category'] = 'Categoria';
$string['course'] = 'Curso';
$string['courseonly'] = 'As contagens incluem as atividades visíveis na seleção, não as obrigações individuais.';
$string['createproposal'] = 'Simular mudança';
$string['currentcourse'] = 'Curso atual';
$string['dailyconcentration'] = 'Concentração de prazos';
$string['dailycount'] = 'Limite indicativo de entregas por dia';
$string['dailycountlabel'] = 'Entregas no dia';
$string['dailyweight'] = 'Limite indicativo de carga ponderada diária';
$string['datasource'] = 'Origem';
$string['day'] = 'Dia';
$string['deadline'] = 'Prazo';
$string['exportcsv'] = 'Exportar CSV';
$string['filter'] = 'Filtrar';
$string['fri'] = 'Sex';
$string['history'] = 'Histórico de cenários';
$string['improved'] = 'Conflitos aliviados';
$string['indicative'] = 'Os valores são faixas agregadas para preservar privacidade; os pesos não representam esforço real medido.';
$string['invaliddate'] = 'A data proposta ou intervalo informado é inválido.';
$string['invalidmodule'] = 'A atividade não está disponível ou não está visível.';
$string['invalidpolicy'] = 'Valores inválidos para a política de planejamento.';
$string['invalidstate'] = 'A proposta não pode ser alterada nesta situação.';
$string['limitexceeded'] = 'O grupo ultrapassa o limite de cálculo configurado. Não foram divulgados resultados parciais.';
$string['local/deadlineplanner:apply'] = 'Aprovar mudanças de prazos';
$string['local/deadlineplanner:configure'] = 'Configurar regras de planejamento';
$string['local/deadlineplanner:simulate'] = 'Criar simulações de prazos';
$string['local/deadlineplanner:view'] = 'Visualizar o planejamento de prazos';
$string['local/deadlineplanner:viewall'] = 'Visualizar planejamento institucional';
$string['manualnotice'] = 'Esta versão não altera datas automaticamente. Após aprovar, use o editor nativo do Moodle para aplicar o prazo proposto e revisar as demais configurações.';
$string['maxlearners'] = 'Máximo de estudantes analisados em cada simulação';
$string['mon'] = 'Seg';
$string['noactivities'] = 'Nenhuma entrega elegível no período informado.';
$string['noduedate'] = 'A atividade não tem prazo de entrega ou encerramento definido.';
$string['none'] = 'Nenhum';
$string['noscenarios'] = 'Não há propostas registradas para este curso.';
$string['openeditor'] = 'Abrir editor da atividade';
$string['originaldate'] = 'Prazo original';
$string['periodend'] = 'Data final';
$string['periodstart'] = 'Data inicial';
$string['pluginname'] = 'Planejamento de prazos acadêmicos';
$string['policyconfiguration'] = 'Configurar política de planejamento do curso';
$string['policysaved'] = 'Política de planejamento do curso salva.';
$string['privacy:audit'] = 'Histórico de auditoria das propostas';
$string['privacy:metadata'] = 'O planejamento de prazos armazena propostas, autores, revisores e auditoria. Não persiste a carga individual dos estudantes.';
$string['privacy:scenario'] = 'Propostas armazenadas';
$string['privacyhint'] = 'Esta visualização inclui somente atividades visíveis no curso selecionado. Comparações entre cursos utilizam agregações protegidas e pesos indicativos, sem revelar nomes de estudantes ou dados de outros cursos.';
$string['privacymin'] = 'Tamanho mínimo do grupo para privacidade (mínimo obrigatório: 5)';
$string['privacyrestricted'] = 'Os detalhes de impacto entre cursos foram ocultados para proteger turmas pequenas.';
$string['proposalstatus'] = 'Situação da proposta de prazo';
$string['proposalstatusbody'] = 'Situação da proposta de planejamento de prazos: {$a}';
$string['proposeddate'] = 'Data proposta';
$string['ratelimited'] = 'Você atingiu o limite de 30 simulações nas últimas 24 horas.';
$string['refreshdeadlines'] = 'Atualizar capturas dos prazos acadêmicos';
$string['sat'] = 'Sáb';
$string['scenario'] = 'Cenário';
$string['scenarioapproved'] = 'Proposta aprovada. As datas da atividade não foram alteradas.';
$string['scenariocreated'] = 'Simulação registrada.';
$string['simulate'] = 'Simular sem alterar o Moodle';
$string['staleproposal'] = 'As datas da atividade foram alteradas. Gere uma nova proposta antes de aprovar ou aplicar.';
$string['status'] = 'Situação';
$string['statusapproved'] = 'Aprovado';
$string['statusarchived'] = 'Arquivado';
$string['statusdraft'] = 'Rascunho';
$string['suggestionhint'] = 'As sugestões usam somente o curso atual. Confira os demais cursos pela simulação.';
$string['suggestions'] = 'Possíveis datas com menor concentração';
$string['sun'] = 'Dom';
$string['supportedprovider'] = 'Adaptador de atividade';
$string['thu'] = 'Qui';
$string['tue'] = 'Ter';
$string['type'] = 'Tipo';
$string['unsupportedmodule'] = 'Não existe um adaptador seguro de prazos para esta atividade.';
$string['wed'] = 'Qua';
$string['weeklycount'] = 'Limite indicativo de entregas por semana';
$string['weeklycountlabel'] = 'Entregas na semana';
$string['weightassign'] = 'Peso indicativo de tarefas';
$string['weightquiz'] = 'Peso indicativo de questionários';
$string['worsened'] = 'Conflitos adicionais';
