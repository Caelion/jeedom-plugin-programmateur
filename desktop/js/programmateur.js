/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/* Fonction pour l'ajout de commande, appellé automatiquement par le plugin */
function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = {configuration: {}};
	}
	if (!isset(_cmd.configuration)) {
		_cmd.configuration = {};
	}
	if (init(_cmd.logicalId) == 'refresh') {
		var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
		tr += '<td>';
			tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
		tr += '</td>';
		tr += '<td></td>';
		tr += '<td></td>';
		tr += '<td></td>';
		tr += '<td>';
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
		tr += '</td>';
		tr += '</tr>';
	} else {
		var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';

		tr += '<td>';
		tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
		if (init(_cmd.type) == 'action') {
			tr += 'Sur : <select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;width:calc(100% - 50px);display:inline" title="{{La valeur de la commande vaut par défaut la commande}}">';
			tr += '<option value="">Aucune</option>';
			tr += '</select>';
		}
		tr += '</td>';

		tr += '<td>';
		tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
		tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
		tr += '</td>';

		tr += '<td>';
		if (init(_cmd.type) == 'action' && init(_cmd.subType) == 'other') {
			tr += '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdId" style=margin-top:5px;" title="Commande d\'information à mettre à jour">';
			tr += '<option value="">Aucune</option>';
			tr += '</select>';
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdToValue" placeholder="Valeur de l\'information" style="margin-top:5px;">';
		} else if (init(_cmd.type) == 'action' && init(_cmd.subType) == 'slider') {
			tr += '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="infoName" style="margin-top:5px;" title="Commande d\'information à mettre à jour">';
			tr += '<option value="">Aucune</option>';
			tr += '</select>';
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" placeholder="Valeur de l\'information" style="margin-top:5px;">';
		}
		tr += '</td>';

		tr += '<td>';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span> ';
		tr += '<br>';
		tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;display:inline-block;">';
		tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;display:inline-block;">';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;display:inline-block;margin-right:5px;">';
		tr += '</td>';

		tr += '<td>';
		if (is_numeric(_cmd.id)) {
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
		}
		tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
		tr += '</td>';

		tr += '</tr>';
    }

	$('#table_cmd tbody').append(tr);
	var tr = $('#table_cmd tbody tr:last');
	jeedom.eqLogic.builSelectCmd({
		id: $('.eqLogicAttr[data-l1key=id]').value(),
		filter: {type: 'info'},
		error: function (error) {
			$('#div_alert').showAlert({message: error.message, level: 'danger'});
		},
		success: function (result) {
			tr.find('.cmdAttr[data-l1key=value]').append(result);
			tr.find('.cmdAttr[data-l1key=configuration][data-l2key=updateCmdId]').append(result);
			tr.find('.cmdAttr[data-l1key=configuration][data-l2key=infoName]').append(result);
			tr.setValues(_cmd, '.cmdAttr');
			jeedom.cmd.changeType(tr, init(_cmd.subType));
		}
	});
}

// Fonction pour ajouter la sélection des commandes
$("#bt_selectCommandeOn").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'action', subType: 'other'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=CommandeOn]').value(result.human);
	});
});
$("#bt_selectCommandeOff").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'action', subType: 'other'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=CommandeOff]').value(result.human);
	});
});
$("#bt_selectCommandeJF").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'binary'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=CommandeJF]').value(result.human);
	});
});
$("#bt_selectCommandeMode").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=CommandeMode]').value(result.human);
	});
});