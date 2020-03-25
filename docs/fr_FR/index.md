Présentation 
===

Plugin permettant d'ajouter un programmateur pour une commande.

Configuration
===

Aucun paramètre au niveau du plugin.

Une fois le plugin installé, vous pouvez ajouter directement un équipement dans le menu habituel.

Le paramétrage est le suivant :
- Spécification de la commande à exécuter à l'heure du déclenchement.
- Optionnel : Spécification la commande à exécuter à l'heure du déclenchement + X minutes.
*Note : si le timer est à 0 minute : la commande ne sera pas exécutée*
- Optionnel : Exlusion des jours fériés : pour se faire, cocher la case et spécifier une information binaire avertissant du jour férié : 1 = jour férié / 0 = jour standard
- Optionnel : Exclusion des jours sur un mode particulier : pour se faire, cocher la case et spécifier une information string de mode ainsi que le nom du mode à exclure

Utilisation
===

Une fois les informations renseignées, vous pouverez directement aller sur le dashboard pour configurer le plugin.
Il présente :
- un bouton pour l'actif / le désactiver
- une case à cocher par jour (L/M/M/J/V/S/D)
- des boutons +/- pour régler l'heure et les minutes du déclenchement.
- des boutons +/- pour régler la durée en minutes avant l'exécutioner de la seconde commande.

Fonctionnement
===

A chaque mise à jour d'un des paramètres du plugin au niveau du widget :
- L'ensemble des futures programmations de l'action On est supprimée.
- Une nouvelle analyse est réalisée avec éventuellement la programmation de l'action On.

A l'exécution de l'action On :
- L'ensemble des futures programmations de l'action Off est supprimée.
- Une nouvelle analyse est réalisée avec éventuellement la programmation de l'action Off sur la base des informations renseignées au moment de l'action On.
