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
 * French language pack for Cohort Manager
 *
 * @package    local_cohortmanager
 * @category   string
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Gestionnaire de Cohortes';

// App component strings
$string['cohortmanager'] = 'Gestionnaire de Cohortes';
$string['newcohort'] = 'Nouveau';
$string['addcohort'] = 'Ajouter une cohorte';
$string['searchcohorts'] = 'Rechercher...';
$string['loadingcohorts'] = 'Chargement...';
$string['name'] = 'Nom';
$string['idnumber'] = 'Nº ID';
$string['description'] = 'Description';
$string['visible'] = 'Visible';
$string['hidden'] = 'Masqué';
$string['actions'] = 'Actions';
$string['edit'] = 'Modifier';
$string['delete'] = 'Supprimer';
$string['id'] = 'ID';
$string['page'] = 'Page';
$string['of'] = 'sur';
$string['nocoortsfound'] = 'Aucune trouvée';
$string['nocoortsfounddesc'] = 'Aucun résultat trouvé.';
$string['createyourfirstcohort'] = 'Créez la première pour commencer.';
$string['createnewcohort'] = 'Ajouter Nouveau';
$string['cancel'] = 'Annuler';
$string['save'] = 'Enregistrer';
$string['nodecription'] = 'Sans description';
$string['nodescription'] = 'Sans description';
$string['failedtoloadcohorts'] = 'Échec du chargement. Veuillez réessayer.';
$string['failedtodeletecohort'] = 'Échec de la suppression. Veuillez réessayer.';
$string['failedtosavecohort'] = 'Échec de l\'enregistrement. Veuillez réessayer.';
$string['deleteconfirmation'] = 'Êtes-vous sûr de vouloir supprimer "%s"?';
$string['deletethiscohort'] = 'Supprimer';
$string['cohortdeletedsuccessfully'] = 'Supprimée avec succès';
$string['instanceexists'] = 'Une instance d\'inscription existe déjà avec cette cohorte et ce rôle dans le cours. Essayez un autre rôle.';

// CohortCreate component strings
$string['basicinformation'] = 'Informations de Base';
$string['cohortnamedescription'] = 'Le nom à afficher';
$string['makecohortvisible'] = 'Rendre visible aux utilisateurs';
$string['theme'] = 'Thème';
$string['settings'] = 'Paramètres';
$string['category'] = 'Catégorie';
$string['systemcategory'] = 'Système';
$string['coursecategorybyidnumber'] = 'Par Nº ID';
$string['coursecategorybyid'] = 'Par ID';
$string['entercoursecategoryidnumber'] = 'Saisissez le nº ID de la catégorie';
$string['entercoursecategoryid'] = 'Saisissez l\'ID de la catégorie';
$string['reset'] = 'Réinitialiser';
$string['creating'] = 'Ajout...';
$string['createcohort'] = 'Ajouter';
$string['failedtocreatecohort'] = 'Échec de l\'ajout. Veuillez réessayer.';
$string['resetform'] = 'Réinitialiser le Formulaire';
$string['pleaseentercohortname'] = 'Veuillez saisir un nom';
$string['pleaseenteridnumber'] = 'Veuillez saisir un nº ID';
$string['cohortcreatedsuccessfully'] = 'Ajoutée avec succès';
$string['cohortname'] = 'Nom';
$string['entercohortname'] = 'Saisissez le nom';
$string['enteridnumber'] = 'Saisissez le nº ID';
$string['idnumberdescription'] = 'L\'identifiant unique';
$string['entercohortdescription'] = 'Saisissez la description';
$string['cohortdescription'] = 'Une brève description';

// CohortDetail component strings
$string['loadingcohortdetails'] = 'Chargement des détails...';
$string['cohortnotfound'] = 'Non Trouvée';
$string['failedtoloadcohortdetails'] = 'Échec du chargement des détails. Veuillez réessayer.';
$string['backtolist'] = 'Retour à la Liste';
$string['retry'] = 'Réessayer';
$string['cohortnotfounddetails'] = 'Elle a peut-être été supprimée ou n\'existe pas.';
$string['details'] = 'Détails';
$string['cohortportrait'] = 'Portrait de la Cohorte';
$string['members'] = 'Membres';
$string['memberscount'] = 'membres';
$string['enrolinstances'] = 'Instances d\'Inscription';
$string['totalenrolments'] = 'Total des Inscrits';
$string['roleassignments'] = 'Attributions de Rôles';
$string['visibility'] = 'Visibilité';
$string['nodescriptionprovided'] = 'Aucune description fournie.';
$string['customfields'] = 'Champs Personnalisés';
$string['cohortmembers'] = 'Membres';
$string['nomembersfound'] = 'Aucun Membre Trouvé';
$string['nomembersfounddescription'] = 'Aucun membre trouvé dans cette cohorte. Ajoutez des membres pour commencer.';
$string['backtocohortlist'] = 'Retour à la Liste';
$string['username'] = 'Identifiant';
$string['email'] = 'Courriel';

// CohortEdit component strings
$string['loadingcohortdata'] = 'Chargement des données...';
$string['failedtoloadcohortdata'] = 'Échec du chargement des données. Veuillez réessayer.';
$string['editcohort'] = 'Modifier';
$string['failedtoupdatecohort'] = 'Échec de la mise à jour. Veuillez réessayer.';
$string['saving'] = 'Enregistrement...';
$string['savechanges'] = 'Enregistrer les Modifications';
$string['cohortupdatedsuccessfully'] = 'Mise à jour réussie';

// ThemeSelect component strings
$string['themedescription'] = 'Sélectionnez un thème';

// ContextSelect component strings
$string['context'] = 'Contexte';
$string['selectcontext'] = 'Sélectionner le contexte';
$string['searchcontext'] = 'Rechercher des contextes...';
$string['nocontextsfound'] = 'Aucun contexte trouvé';
$string['entercoursecontextid'] = 'Saisissez l\'ID du contexte du cours';
$string['systemcontext'] = 'Système';
$string['loading'] = 'Chargement...';
$string['search'] = 'Rechercher';

// CohortDelete component strings
$string['deletecohort'] = 'Supprimer';

// CohortCustomFieldsManagement component strings
$string['customfieldsmanagement'] = 'Gestionnaire de Champs Personnalisés';
$string['back'] = 'Retour';
$string['backtohome'] = 'Retour à l\'accueil';

// Custom field form strings
$string['customfieldssaved'] = 'Champs personnalisés enregistrés avec succès.';
$string['errorprocessingcustomfields'] = 'Erreur lors du traitement des champs personnalisés. Veuillez réessayer.';
$string['customfieldnotvalid'] = 'Champs personnalisés invalides.';

// CohortMembersAddModal component strings
$string['addmembers'] = 'Ajouter des Membres';
$string['add'] = 'Ajouter';
$string['membersadded'] = 'Membres ajoutés avec succès';

// Delete membership confirmation strings
$string['delete_membership_confirmation'] = 'Êtes-vous sûr de vouloir supprimer les {$a} membres sélectionnés?';
$string['delete_membership_warning'] = 'Avertissement : Cette action est permanente et irréversible.';
$string['selected_members_list'] = 'Sélectionnés : {$a}';

// CohortEnrolInstancesPartial component strings
$string['addenrolinstance'] = 'Ajouter une Instance d\'Inscription';
$string['cohortenrolinstances'] = 'Instances d\'Inscription';
$string['instancescount'] = 'instances';
$string['loadingenrolinstances'] = 'Chargement des instances d\'inscription...';
$string['noenrolinstancesfound'] = 'Aucune Instance d\'Inscription Trouvée';
$string['noenrolinstancesfounddescription'] = 'Aucune instance d\'inscription trouvée.';
$string['enrolinstanceslist'] = 'Instances d\'Inscription';
$string['refresh'] = 'Actualiser';
$string['course'] = 'Cours';
$string['enrolmethod'] = 'Méthode';
$string['status'] = 'Statut';
$string['active'] = 'Actif';
$string['inactive'] = 'Inactif';
$string['cohortenrol'] = 'Cohorte';
$string['view'] = 'Voir';
$string['deleteenrolinstance'] = 'Supprimer l\'instance d\'inscription';
$string['deleteenrolinstanceconfirmation'] = 'Êtes-vous sûr de vouloir supprimer l\'instance d\'inscription pour "%s"?';
$string['deleteenrolinstancewarning'] = 'Avertissement : Cela supprimera {$a} inscriptions d\'utilisateurs de ce cours.';
$string['enrolinstancedeletedsuccessfully'] = 'Instance d\'inscription supprimée avec succès';
$string['searchenrolinstances'] = 'Rechercher des instances d\'inscription...';
$string['filterbystatus'] = 'Filtrer par statut';
$string['all'] = 'Tous';
$string['togglestatus'] = 'Basculer le statut';
$string['enrolinstancestatusupdated'] = 'Statut de l\'instance d\'inscription mis à jour avec succès';
$string['errortogglingstatus'] = 'Erreur lors de la mise à jour du statut de l\'instance d\'inscription';
$string['enrolinstanceupdated'] = 'Instance d\'inscription mise à jour avec succès';

// Roles management component strings
$string['rolesmanagement'] = 'Gestion des Rôles';
$string['usercontextroles'] = 'Rôles en Contexte Utilisateur';
$string['searchroles'] = 'Rechercher des rôles...';
$string['loadingroles'] = 'Chargement des rôles...';
$string['rolename'] = 'Nom du Rôle';
$string['roleshortname'] = 'Nom Court';
$string['roleid'] = 'ID';
$string['rolesortorder'] = 'Ordre';
$string['rolearchetype'] = 'Archétype';
$string['roleactions'] = 'Actions';
$string['editrole'] = 'Modifier';
$string['deleterole'] = 'Supprimer';
$string['createnewrole'] = 'Créer un Nouveau Rôle';
$string['addnewrole'] = 'Ajouter un nouveau rôle';
$string['rolecreatedsuccessfully'] = 'Rôle créé avec succès';
$string['roleupdatedsuccessfully'] = 'Rôle mis à jour avec succès';
$string['roledeletedsuccessfully'] = 'Rôle supprimé avec succès';
$string['rolenotfound'] = 'Rôle non trouvé';
$string['roleexists'] = 'Un rôle avec ce nom court existe déjà';
$string['noassignableroles'] = 'Aucun rôle attribuable disponible dans le contexte utilisateur.';
$string['roledescriptionplaceholder'] = 'Saisissez la description (facultatif)';
$string['rolearchetypeplaceholder'] = 'Sélectionnez l\'archétype (facultatif)';
$string['rolecreate'] = 'Créer le Rôle';
$string['roleupdate'] = 'Mettre à Jour le Rôle';
$string['roledeleteconfirmation'] = 'Êtes-vous sûr de vouloir supprimer "%s"? Cette action est irréversible.';
$string['deletethisrole'] = 'Supprimer ce rôle';
$string['cannotdeletethisrole'] = 'Ce rôle ne peut pas être supprimé car il fait partie du système.';
$string['cannotdeleterolewithid'] = 'Impossible de supprimer le rôle avec l\'ID {$a}.';
$string['rolesearchplaceholder'] = 'Rechercher par nom, nom court ou personnalisé...';
$string['rolescount'] = 'rôles';
$string['norolesfound'] = 'Aucun rôle trouvé';
$string['norolesfounddesc'] = 'Aucun rôle trouvé.';
$string['createyourfirstrole'] = 'Créez votre premier rôle pour commencer.';

// DynamicCohortMembersTable component strings
$string['searchmembers'] = 'Rechercher des membres...';
$string['first'] = 'Premier';
$string['last'] = 'Dernier';
$string['columns'] = 'Colonnes';
$string['city'] = 'Ville';
$string['country'] = 'Pays';
$string['showing'] = 'Affichage';
$string['to'] = 'à';
$string['previous'] = 'Précédent';
$string['next'] = 'Suivant';
$string['failedtofetchmembers'] = 'Échec de la récupération des membres';
$string['anerroroccurred'] = 'Une erreur est survenue';
$string['unknown'] = 'Inconnu';
$string['errordeletingmembers'] = 'Erreur lors de la suppression des membres';

// CohortEnrolInstancesSearchModal component strings
$string['searchcourses'] = 'Rechercher des cours';
$string['searchcoursesplaceholder'] = 'Rechercher des cours...';
$string['searching'] = 'Recherche...';
$string['nocoursesfound'] = 'Aucun cours trouvé';
$string['selectedcourses'] = 'Cours sélectionnés';
$string['coursealreadyselected'] = 'Cours déjà sélectionné';
$string['noselectedcourses'] = 'Aucun cours sélectionné';
$string['role'] = 'Rôle';
$string['group'] = 'Groupe';
$string['remove'] = 'Supprimer';
$string['enroled'] = 'Inscrits';
$string['groupmembers'] = 'Membres du Groupe';
$string['enrolinstanceadded'] = 'Instance d\'inscription ajoutée avec succès';
$string['errorcreatingenrolinstances'] = 'Erreur lors de la création des instances d\'inscription. Veuillez réessayer.';

// Cohort role assignments (tool_cohortroles integration) strings
$string['cohortroles'] = 'Rôles de Cohorte';
$string['cohortroleassignments'] = 'Attributions';
$string['cohortroleassignmentscount'] = 'attributions';
$string['loadingcohortroles'] = 'Chargement des attributions de rôles...';
$string['noroughassignmentsfound'] = 'Aucune Attribution Trouvée';
$string['noroughassignmentsfounddescription'] = 'Aucune attribution trouvée. Attribuez des utilisateurs avec des rôles pour établir une hiérarchie.';
$string['createyourfirstassignment'] = 'Créez votre première attribution pour commencer.';
$string['addroleassignment'] = 'Ajouter une Attribution';
$string['assigneduser'] = 'Utilisateur';
$string['assignedrole'] = 'Rôle';
$string['assignedcohort'] = 'Cohorte';
$string['assignedtime'] = 'Attribué Le';
$string['deleteassignment'] = 'Supprimer l\'attribution';
$string['deleteassignmentconfirmation'] = 'Êtes-vous sûr de vouloir supprimer cette attribution? Elle sera supprimée lors de la prochaine synchronisation.';
$string['cohortroleassignmentcreated'] = 'Attribution créée. Elle prendra effet après la prochaine synchronisation.';
$string['cohortroleassignmentdeleted'] = 'Attribution supprimée avec succès.';
$string['cohortroleassignmentexists'] = 'Cette attribution existe déjà.';
$string['backgroundsyncnote'] = 'Les attributions prennent effet après la synchronisation planifiée.';
$string['synclastsync'] = 'Dernière';
$string['syncnextsync'] = 'Prochaine';
$string['syncnever'] = 'Jamais';
$string['selectuser'] = 'Sélectionnez un utilisateur';
$string['searchusers'] = 'Rechercher des utilisateurs...';
$string['selectrole'] = 'Sélectionnez un rôle';
$string['nousersfound'] = 'Aucun utilisateur trouvé';
$string['assignroletouser'] = 'Attribuer un Rôle';
$string['userrolecohortinfo'] = 'Attribue un rôle de contexte utilisateur sur tous les membres.';
$string['assignedon'] = 'Attribué le';
$string['deletethisassignment'] = 'Supprimer cette attribution';
$string['assigneduserscount'] = 'Attributions';
$string['formvalidationerror'] = 'Veuillez corriger les erreurs dans le formulaire.';
$string['perpage'] = 'Par page';