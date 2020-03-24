<?php

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

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class programmateur extends eqLogic {
	/* **************************Attributs****************************** */


	/* ***********************Methode static*************************** */

	public static function nextprog_on($_params) {
log::add('programmateur', 'debug','Lancement de nextprog_on');
		$eqLogic = eqLogic::byId($_params['eq_id']);
		if (is_object($eqLogic)) {
			$cmd_state = $eqLogic->getCmd(null, 'etat');
			if (!is_object($cmd_state) || $cmd_state->execCmd() != 1) {// On s'assure que la planification est toujours sur on sinon on quitte
				return;
			}
			if (isset($_params['on']) && $_params['on'] != '') {
				cmd::byId(str_replace('#', '',$_params['on']))->execCmd();
log::add('programmateur', 'debug','Nextprog - ' . $_params['eq_id'] . ' - On - '.$_params['on']);
			}
log::add('programmateur', 'debug','Nextprog - ' . $_params['eq_id'] . ' - Delay - '.$_params['delay'].' secondes');
			if (isset($_params['delay']) && $_params['delay'] > 0 && isset($_params['off']) && $_params['off'] != '') {
				$heure_timestamp = time()+$_params['delay'];
//log::add('programmateur', 'debug','Nextprog - Timestamp - ' . $heure_timestamp);
				programmateur::cleannextprog($_params['eq_id'],'nextprog_off');
				$cron = new cron();
				$cron->setClass('programmateur');
				$cron->setFunction('nextprog_off');
				$cron->setOption(array('eq_id' => $_params['eq_id'], 'off' => $_params['off']));
				$cron->setOnce(1);
				$cron->setSchedule(cron::convertDateToCron($heure_timestamp));
				$cron->save();
			}
		}
	}

	public static function nextprog_off($_params) {
log::add('programmateur', 'debug','Lancement de nextprog_off');
		$eqLogic = eqLogic::byId($_params['eq_id']);
		if (is_object($eqLogic)) {
			$cmd_state = $eqLogic->getCmd(null, 'etat');
			if (!is_object($cmd_state)) {// On s'assure que la commande etat existe sinon on quitte
				return;
			}
			if (isset($_params['off'])) {
				cmd::byId(str_replace('#', '',$_params['off']))->execCmd();
log::add('programmateur', 'debug','Nextprog - ' . $_params['eq_id'] . ' - Off - '.$_params['off']);
			}
		}
	}

	public function cleannextprog($equipement,$fonction,$array,$type) {
//log::add('programmateur', 'debug', 'Exécution de la fonction cleannextprog ' . $type );
		$count=0;
		$crons = cron::searchClassAndFunction('programmateur', $fonction, '"eq_id":' . $equipement);
		if (is_array($crons) && count($crons) > 0) {
			foreach ($crons as $cron) {
				$arrayprogramme = array_values($cron->getOption())[0].array_values($cron->getOption())[1].array_values($cron->getOption())[2].array_values($cron->getOption())[3].array_values($cron->getOption())[4];
				$arrayaprogrammer = $array['eq_id'].$array['delay'].$array['on'].$array['off'].$array['timestamp'];
//log::add('programmateur', 'debug', 'Programmé ' . $arrayprogramme);
//log::add('programmateur', 'debug', 'Aprogrammer ' . $arrayaprogrammer);
				if ($arrayprogramme == $arrayaprogrammer) {
log::add('programmateur', 'debug', 'Exécution de la fonction cleannextprog '.$fonction.' - '.$equipement.' - '.$type.' - Pas de changement du cron');
					$count=$count+1;
			} else {
log::add('programmateur', 'debug', 'Exécution de la fonction cleannextprog '.$fonction.' - '.$equipement.' - '.$type.' - Suppression du cron');
					if ($cron->getState() != 'run') {
						$cron->remove();
					}
				}
			}
		}
		if ($type == 'cron' && $count == 0) {
log::add('programmateur', 'debug', 'Exécution de la fonction cleannextprog '.$fonction.' - '.$equipement.' - Nouveau cron');
			$cron = new cron();
			$cron->setClass('programmateur');
			$cron->setFunction('nextprog_on');
			$cron->setOption($array);
			$cron->setOnce(1);
			$cron->setSchedule(cron::convertDateToCron($array['timestamp']));
			$cron->save();
		}
	}

	/* Fonction exécutée automatiquement toutes les minutes par Jeedom */
	public static function cron() {
//log::add('programmateur', 'debug', 'Exécution de la fonction cron');
		foreach (self::byType('programmateur') as $programmateur) {// Parcours tous les équipements du plugin
			if ($programmateur->getIsEnable() == 1) { // Vérification que l'équipement est actif
				$cmd = $programmateur->getCmd(null, 'etat');// Retourne la commande "etat" si elle existe
				if (is_object($cmd)) {//Si la commande existe
					$cmdValue = $cmd->execCmd();
					if ($cmdValue == 1) {// Si le programmateur est sur On
						$today=date('N');
						$lundi=$programmateur->getCmd(null, 'lundi')->execCmd();
						$mardi=$programmateur->getCmd(null, 'mardi')->execCmd();
						$mercredi=$programmateur->getCmd(null, 'mercredi')->execCmd();
						$jeudi=$programmateur->getCmd(null, 'jeudi')->execCmd();
						$vendredi=$programmateur->getCmd(null, 'vendredi')->execCmd();
						$samedi=$programmateur->getCmd(null, 'samedi')->execCmd();
						$dimanche=$programmateur->getCmd(null, 'dimanche')->execCmd();
						$duree=$programmateur->getCmd(null, 'duree')->execCmd();

						$JF_box=$programmateur->getConfiguration('JF');
						if ($programmateur->getConfiguration('CommandeJF') != '') {
							$JF=cmd::byId(str_replace('#', '',$programmateur->getConfiguration('CommandeJF')))->execCmd();
						} else {$JF = 0;}
//log::add('programmateur', 'debug', 'JF : ' . $JF_box.$JF);
						$Mode_box=$programmateur->getConfiguration('Mode');
						if ($programmateur->getConfiguration('CommandeMode') != '') {
							$Mode=cmd::byId(str_replace('#', '',$programmateur->getConfiguration('CommandeMode')))->execCmd();
							if ($Mode==$programmateur->getConfiguration('ExclMode')){
								$Mode = 1;
							} else {
								$Mode = 0;
							}
						} else {$Mode = 0;}
//log::add('programmateur', 'debug', 'Mode : ' . $Mode_box.$Mode);

						$heure = $programmateur->getCmd(null, 'horaire')->execCmd();
						//Traitement des 0
						if ($heure <1000 && $heure >=100) {
							$heure = '0'.$heure;
						} else if ($heure <100 && $heure >0) {
							$heure = '00'.$heure;
						} else if ($heure == 0) {
							$heure = '000'.$heure;
						}
						$heure_timestamp = strtotime(date('d-m-Y') . ' ' . $heure);//Correction du GMT+1
//log::add('programmateur', 'debug', 'timestamp programmateur : ' . $heure_timestamp);//Timestamp de la programmateur du jour
//log::add('programmateur', 'debug', 'timestamp actuel : ' . strtotime(date('H:i')));
						$array = array('eq_id' => intval($programmateur->getId()), 'delay' => $duree*60, 'on' => $programmateur->getConfiguration('CommandeOn'), 'off' => $programmateur->getConfiguration('CommandeOff'), 'timestamp' => $heure_timestamp);
						programmateur::cleannextprog($programmateur->getId(),'nextprog_on',$array,'clean');

						if ((($JF_box == 1 && $JF == 0) || $JF_box == 0) && (($Mode_box == 1 && $Mode == 0) || $Mode_box == 0) && (($today == 1 && $lundi == 1)||($today == 2 && $mardi == 1)||($today == 3 && $mercredi == 1)||($today == 4 && $jeudi == 1)||($today == 5 && $vendredi == 1)||($today == 6 && $samedi == 1)||($today == 7 && $dimanche == 1))) {
							if (strtotime(date('H:i')) > $heure_timestamp) {
//log::add('programmateur', 'debug', 'cron depasse');
							} else {
								programmateur::cleannextprog($programmateur->getId(),'nextprog_on',$array,'cron');
							}
						}
					}
				}
			}
		}
	}


	/* Fonction exécutée automatiquement toutes les heures par Jeedom
	public static function cronHourly() {
		
	}
	*/

	/* Fonction exécutée automatiquement tous les jours par Jeedom
	public static function cronDaily() {

	}
	*/

	/* *********************Méthodes d'instance************************* */

	public function preInsert() {
		
	}

	public function postInsert() {
		
	}

	public function preSave() {
		
	}

	public function postSave() {
log::add('programmateur', 'debug', 'Exécution de la fonction postSave');
		//Création des commandes
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new programmateurCmd();
			$refresh->setLogicalId('refresh');
			$refresh->setName(__('Rafraichir', __FILE__));
			$refresh->setOrder($order++);
		}
		$refresh->setEqLogic_id($this->getId());
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->save();

		$info = $this->getCmd(null, 'etat');
		if (!is_object($info)) {
			$info = new programmateurCmd();
			$info->setLogicalId('etat');
			$info->setName(__('Etat', __FILE__));
			$info->setOrder($order++);
			$info->setIsVisible(0);
			$info->setisHistorized(1);
		}
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('binary');
		$info->save();

		$action = $this->getCmd(null, 'on');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('on');
			$action->setName(__('On', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','toggle');
			$action->setTemplate('mobile','toggle');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 1);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'off');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('off');
			$action->setName(__('Off', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','toggle');
			$action->setTemplate('mobile','toggle');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$info = $this->getCmd(null, 'lundi');
		if (!is_object($info)) {
			$info = new programmateurCmd();
			$info->setLogicalId('lundi');
			$info->setName(__('Lundi', __FILE__));
			$info->setOrder($order++);
			$info->setIsVisible(0);
			$info->setValue(1);
		}
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('binary');
		$info->save();

		$action = $this->getCmd(null, 'lun_on');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('lun_on');
			$action->setName(__('Lun_On', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 1);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'lun_off');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('lun_off');
			$action->setName(__('Lun_Off', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$info = $this->getCmd(null, 'mardi');
		if (!is_object($info)) {
			$info = new programmateurCmd();
			$info->setLogicalId('mardi');
			$info->setName(__('Mardi', __FILE__));
			$info->setOrder($order++);
			$info->setIsVisible(0);
		}
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('binary');
		$info->save();

		$action = $this->getCmd(null, 'mar_on');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('mar_on');
			$action->setName(__('Mar_On', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 1);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'mar_off');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('mar_off');
			$action->setName(__('Mar_Off', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$info = $this->getCmd(null, 'mercredi');
		if (!is_object($info)) {
			$info = new programmateurCmd();
			$info->setLogicalId('mercredi');
			$info->setName(__('Mercredi', __FILE__));
			$info->setOrder($order++);
			$info->setIsVisible(0);
		}
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('binary');
		$info->save();

		$action = $this->getCmd(null, 'mer_on');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('mer_on');
			$action->setName(__('Mer_On', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 1);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'mer_off');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('mer_off');
			$action->setName(__('Mer_Off', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$info = $this->getCmd(null, 'jeudi');
		if (!is_object($info)) {
			$info = new programmateurCmd();
			$info->setLogicalId('jeudi');
			$info->setName(__('Jeudi', __FILE__));
			$info->setOrder($order++);
			$info->setIsVisible(0);
		}
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('binary');
		$info->save();

		$action = $this->getCmd(null, 'jeu_on');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('jeu_on');
			$action->setName(__('Jeu_On', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 1);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'jeu_off');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('jeu_off');
			$action->setName(__('Jeu_Off', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$info = $this->getCmd(null, 'vendredi');
		if (!is_object($info)) {
			$info = new programmateurCmd();
			$info->setLogicalId('vendredi');
			$info->setName(__('Vendredi', __FILE__));
			$info->setOrder($order++);
			$info->setIsVisible(0);
		}
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('binary');
		$info->save();

		$action = $this->getCmd(null, 'ven_on');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('ven_on');
			$action->setName(__('Ven_On', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 1);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'ven_off');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('ven_off');
			$action->setName(__('Ven_Off', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$info = $this->getCmd(null, 'samedi');
		if (!is_object($info)) {
			$info = new programmateurCmd();
			$info->setLogicalId('samedi');
			$info->setName(__('Samedi', __FILE__));
			$info->setOrder($order++);
			$info->setIsVisible(0);
		}
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('binary');
		$info->save();

		$action = $this->getCmd(null, 'sam_on');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('sam_on');
			$action->setName(__('Sam_On', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 1);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'sam_off');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('sam_off');
			$action->setName(__('Sam_Off', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$info = $this->getCmd(null, 'dimanche');
		if (!is_object($info)) {
			$info = new programmateurCmd();
			$info->setLogicalId('dimanche');
			$info->setName(__('Dimanche', __FILE__));
			$info->setOrder($order++);
			$info->setIsVisible(0);
		}
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('binary');
		$info->save();

		$action = $this->getCmd(null, 'dim_on');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('dim_on');
			$action->setName(__('Dim_On', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 1);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'dim_off');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('dim_off');
			$action->setName(__('Dim_Off', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','day');
			$action->setTemplate('mobile','day');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$info = $this->getCmd(null, 'horaire');
		if (!is_object($info)) {
			$info = new programmateurCmd();
			$info->setLogicalId('horaire');
			$info->setName(__('Horaire', __FILE__));
			$info->setOrder($order++);
			$info->setIsVisible(0);
			$info->setisHistorized(1);
			$info->setConfiguration('minValue', 0);
			$info->setConfiguration('maxValue', 2359);
		}
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->save();

		$action = $this->getCmd(null, 'var_horaire');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('var_horaire');
			$action->setName(__('Var_Horaire', __FILE__));
			$action->setOrder($order++);
			$action->setConfiguration('minValue', 0);
			$action->setConfiguration('maxValue', 2359);
			$action->setTemplate('dashboard','time');
			$action->setTemplate('mobile','time');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setEqLogic_id($this->getId());
		$action->setConfiguration('infoName', $info->getId());
		$action->setValue($info->getId());
		$action->setType('action');
		$action->setSubType('slider');
		$action->save();

		$info = $this->getCmd(null, 'duree');
		if (!is_object($info)) {
			$info = new programmateurCmd();
			$info->setLogicalId('duree');
			$info->setName(__('Durée', __FILE__));
			$info->setOrder($order++);
			$info->setIsVisible(0);
			$info->setisHistorized(1);
		}
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->save();

		$action = $this->getCmd(null, 'var_duree');
		if (!is_object($action)) {
			$action = new programmateurCmd();
			$action->setLogicalId('var_duree');
			$action->setName(__('Var_Durée', __FILE__));
			$action->setOrder($order++);
			$action->setTemplate('dashboard','button');
			$action->setTemplate('mobile','button');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
				$arr['step'] = 10;
			$action->setDisplay('parameters', $arr);
		}
		$action->setEqLogic_id($this->getId());
		$action->setConfiguration('infoName', $info->getId());
		$action->setValue($info->getId());
		$action->setType('action');
		$action->setSubType('slider');
		$action->save();
	}

	public function preUpdate() {
		
	}

	public function postUpdate() {
		
	}

	public function preRemove() {
		
	}

	public function postRemove() {
		
	}

	/* Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
	public function toHtml($_version = 'dashboard') {
		
	}
	*/

	/* Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
	public static function postConfig_<Variable>() {
		
	}
	*/

	/* Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
	public static function preConfig_<Variable>() {
		
	}
	*/

	/* **********************Getteur Setteur*************************** */
}

class programmateurCmd extends cmd {
	/* *************************Attributs****************************** */


	/* ***********************Methode static*************************** */


	/* *********************Methode d'instance************************* */

	/* Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
	public function dontRemoveCmd() {
		return true;
	}
	*/

	public function execute($_options = array()) {
log::add('programmateur', 'debug', 'Exécution de la fonction execute');
		if ($this->getLogicalId() == 'refresh') {
			$this->getEqLogic()->refresh();
			return;
		}
		switch ($this->getSubType()) {
			case 'slider':
				$virtualCmd = virtualCmd::byId($this->getConfiguration('infoName'));
				$value = $_options['slider'];
				$result = jeedom::evaluateExpression($value);
				$virtualCmd->event($result);
			break;
		}
	}

	/* **********************Getteur Setteur*************************** */
}