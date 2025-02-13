<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Fisharebest\Webtrees\Http\Routes;

use Aura\Router\Map;
use Fig\Http\Message\RequestMethodInterface;
use Fisharebest\Webtrees\Http\Middleware\AuthAdministrator;
use Fisharebest\Webtrees\Http\Middleware\AuthEditor;
use Fisharebest\Webtrees\Http\Middleware\AuthLoggedIn;
use Fisharebest\Webtrees\Http\Middleware\AuthManager;
use Fisharebest\Webtrees\Http\Middleware\AuthMember;
use Fisharebest\Webtrees\Http\Middleware\AuthModerator;
use Fisharebest\Webtrees\Http\Middleware\AuthVisitor;
use Fisharebest\Webtrees\Http\RequestHandlers\AccountDelete;
use Fisharebest\Webtrees\Http\RequestHandlers\AccountEdit;
use Fisharebest\Webtrees\Http\RequestHandlers\AccountUpdate;
use Fisharebest\Webtrees\Http\RequestHandlers\AddNewFact;
use Fisharebest\Webtrees\Http\RequestHandlers\BroadcastAction;
use Fisharebest\Webtrees\Http\RequestHandlers\BroadcastPage;
use Fisharebest\Webtrees\Http\RequestHandlers\CleanDataFolder;
use Fisharebest\Webtrees\Http\RequestHandlers\ContactAction;
use Fisharebest\Webtrees\Http\RequestHandlers\ContactPage;
use Fisharebest\Webtrees\Http\RequestHandlers\ControlPanel;
use Fisharebest\Webtrees\Http\RequestHandlers\CopyFact;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateMediaObjectAction;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateMediaObjectModal;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateNoteAction;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateNoteModal;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateRepositoryAction;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateRepositoryModal;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateSourceAction;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateSourceModal;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateSubmitterAction;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateSubmitterModal;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateTreeAction;
use Fisharebest\Webtrees\Http\RequestHandlers\CreateTreePage;
use Fisharebest\Webtrees\Http\RequestHandlers\DeleteFact;
use Fisharebest\Webtrees\Http\RequestHandlers\DeletePath;
use Fisharebest\Webtrees\Http\RequestHandlers\DeleteRecord;
use Fisharebest\Webtrees\Http\RequestHandlers\DeleteTreeAction;
use Fisharebest\Webtrees\Http\RequestHandlers\DeleteUser;
use Fisharebest\Webtrees\Http\RequestHandlers\EditFact;
use Fisharebest\Webtrees\Http\RequestHandlers\EditRawFactAction;
use Fisharebest\Webtrees\Http\RequestHandlers\EditRawFactPage;
use Fisharebest\Webtrees\Http\RequestHandlers\EditRawRecordAction;
use Fisharebest\Webtrees\Http\RequestHandlers\EditRawRecordPage;
use Fisharebest\Webtrees\Http\RequestHandlers\ExportGedcomClient;
use Fisharebest\Webtrees\Http\RequestHandlers\ExportGedcomPage;
use Fisharebest\Webtrees\Http\RequestHandlers\ExportGedcomServer;
use Fisharebest\Webtrees\Http\RequestHandlers\FamilyPage;
use Fisharebest\Webtrees\Http\RequestHandlers\GedcomRecordPage;
use Fisharebest\Webtrees\Http\RequestHandlers\HelpText;
use Fisharebest\Webtrees\Http\RequestHandlers\HomePage;
use Fisharebest\Webtrees\Http\RequestHandlers\IndividualPage;
use Fisharebest\Webtrees\Http\RequestHandlers\LoginAction;
use Fisharebest\Webtrees\Http\RequestHandlers\LoginPage;
use Fisharebest\Webtrees\Http\RequestHandlers\Logout;
use Fisharebest\Webtrees\Http\RequestHandlers\Masquerade;
use Fisharebest\Webtrees\Http\RequestHandlers\MediaPage;
use Fisharebest\Webtrees\Http\RequestHandlers\MessageAction;
use Fisharebest\Webtrees\Http\RequestHandlers\MessagePage;
use Fisharebest\Webtrees\Http\RequestHandlers\MessageSelect;
use Fisharebest\Webtrees\Http\RequestHandlers\ModuleAction;
use Fisharebest\Webtrees\Http\RequestHandlers\NotePage;
use Fisharebest\Webtrees\Http\RequestHandlers\PasswordRequestAction;
use Fisharebest\Webtrees\Http\RequestHandlers\PasswordRequestPage;
use Fisharebest\Webtrees\Http\RequestHandlers\PasswordResetAction;
use Fisharebest\Webtrees\Http\RequestHandlers\PasswordResetPage;
use Fisharebest\Webtrees\Http\RequestHandlers\PasteFact;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChanges;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChangesAcceptChange;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChangesAcceptRecord;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChangesAcceptTree;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChangesLogAction;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChangesLogData;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChangesLogDelete;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChangesLogDownload;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChangesLogPage;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChangesRejectChange;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChangesRejectRecord;
use Fisharebest\Webtrees\Http\RequestHandlers\PendingChangesRejectTree;
use Fisharebest\Webtrees\Http\RequestHandlers\PhpInformation;
use Fisharebest\Webtrees\Http\RequestHandlers\Ping;
use Fisharebest\Webtrees\Http\RequestHandlers\PrivacyPolicy;
use Fisharebest\Webtrees\Http\RequestHandlers\RedirectFamilyPhp;
use Fisharebest\Webtrees\Http\RequestHandlers\RedirectGedRecordPhp;
use Fisharebest\Webtrees\Http\RequestHandlers\RedirectIndividualPhp;
use Fisharebest\Webtrees\Http\RequestHandlers\RedirectMediaViewerPhp;
use Fisharebest\Webtrees\Http\RequestHandlers\RedirectNotePhp;
use Fisharebest\Webtrees\Http\RequestHandlers\RedirectRepoPhp;
use Fisharebest\Webtrees\Http\RequestHandlers\RedirectSourcePhp;
use Fisharebest\Webtrees\Http\RequestHandlers\RegisterAction;
use Fisharebest\Webtrees\Http\RequestHandlers\RegisterPage;
use Fisharebest\Webtrees\Http\RequestHandlers\ReorderChildrenAction;
use Fisharebest\Webtrees\Http\RequestHandlers\ReorderChildrenPage;
use Fisharebest\Webtrees\Http\RequestHandlers\ReorderMediaAction;
use Fisharebest\Webtrees\Http\RequestHandlers\ReorderMediaPage;
use Fisharebest\Webtrees\Http\RequestHandlers\ReorderNamesAction;
use Fisharebest\Webtrees\Http\RequestHandlers\ReorderNamesPage;
use Fisharebest\Webtrees\Http\RequestHandlers\ReorderSpousesAction;
use Fisharebest\Webtrees\Http\RequestHandlers\ReorderSpousesPage;
use Fisharebest\Webtrees\Http\RequestHandlers\ReportGenerate;
use Fisharebest\Webtrees\Http\RequestHandlers\ReportListAction;
use Fisharebest\Webtrees\Http\RequestHandlers\ReportListPage;
use Fisharebest\Webtrees\Http\RequestHandlers\ReportSetupAction;
use Fisharebest\Webtrees\Http\RequestHandlers\ReportSetupPage;
use Fisharebest\Webtrees\Http\RequestHandlers\RepositoryPage;
use Fisharebest\Webtrees\Http\RequestHandlers\RobotsTxt;
use Fisharebest\Webtrees\Http\RequestHandlers\SearchAdvancedAction;
use Fisharebest\Webtrees\Http\RequestHandlers\SearchAdvancedPage;
use Fisharebest\Webtrees\Http\RequestHandlers\SearchGeneralAction;
use Fisharebest\Webtrees\Http\RequestHandlers\SearchGeneralPage;
use Fisharebest\Webtrees\Http\RequestHandlers\SearchPhoneticAction;
use Fisharebest\Webtrees\Http\RequestHandlers\SearchPhoneticPage;
use Fisharebest\Webtrees\Http\RequestHandlers\SearchQuickAction;
use Fisharebest\Webtrees\Http\RequestHandlers\SearchReplaceAction;
use Fisharebest\Webtrees\Http\RequestHandlers\SearchReplacePage;
use Fisharebest\Webtrees\Http\RequestHandlers\Select2Family;
use Fisharebest\Webtrees\Http\RequestHandlers\Select2Individual;
use Fisharebest\Webtrees\Http\RequestHandlers\Select2MediaObject;
use Fisharebest\Webtrees\Http\RequestHandlers\Select2Note;
use Fisharebest\Webtrees\Http\RequestHandlers\Select2Repository;
use Fisharebest\Webtrees\Http\RequestHandlers\Select2Source;
use Fisharebest\Webtrees\Http\RequestHandlers\Select2Submitter;
use Fisharebest\Webtrees\Http\RequestHandlers\SelectDefaultTree;
use Fisharebest\Webtrees\Http\RequestHandlers\SelectLanguage;
use Fisharebest\Webtrees\Http\RequestHandlers\SelectNewFact;
use Fisharebest\Webtrees\Http\RequestHandlers\SelectTheme;
use Fisharebest\Webtrees\Http\RequestHandlers\SiteLogsAction;
use Fisharebest\Webtrees\Http\RequestHandlers\SiteLogsData;
use Fisharebest\Webtrees\Http\RequestHandlers\SiteLogsDelete;
use Fisharebest\Webtrees\Http\RequestHandlers\SiteLogsDownload;
use Fisharebest\Webtrees\Http\RequestHandlers\SiteLogsPage;
use Fisharebest\Webtrees\Http\RequestHandlers\SourcePage;
use Fisharebest\Webtrees\Http\RequestHandlers\VerifyEmail;

/**
 * Routing table for web requests
 */
class WebRoutes
{
    public function load(Map $router): void
    {
        // Admin routes.
        $router->attach('', '/admin', static function (Map $router) {
            $router->extras([
                'middleware' => [AuthAdministrator::class]
            ]);

            $router->get(ControlPanel::class, '/control-panel', ControlPanel::class);
            $router->get('admin-fix-level-0-media', '/fix-level-0-media', 'Admin\FixLevel0MediaController::fixLevel0Media');
            $router->post('admin-fix-level-0-media-action', '/fix-level-0-media', 'Admin\FixLevel0MediaController::fixLevel0MediaAction');
            $router->get('admin-fix-level-0-media-data', '/fix-level-0-media-data', 'Admin\FixLevel0MediaController::fixLevel0MediaData');
            $router->get('admin-webtrees1-thumbs', '/webtrees1-thumbs', 'Admin\ImportThumbnailsController::webtrees1Thumbnails');
            $router->post('admin-webtrees1-thumbs-action', '/webtrees1-thumbs', 'Admin\ImportThumbnailsController::webtrees1ThumbnailsAction');
            $router->get('admin-webtrees1-thumbs-data', '/webtrees1-thumbs-data', 'Admin\ImportThumbnailsController::webtrees1ThumbnailsData');
            $router->get('modules', '/modules', 'Admin\ModuleController::list');
            $router->post('modules-update', '/modules', 'Admin\ModuleController::update');
            $router->get('analytics', '/analytics', 'Admin\ModuleController::listAnalytics');
            $router->post('analytics-update', '/analytics', 'Admin\ModuleController::updateAnalytics');
            $router->get('blocks', '/blocks', 'Admin\ModuleController::listBlocks');
            $router->post('blocks-update', '/blocks', 'Admin\ModuleController::updateBlocks');
            $router->get('charts', '/charts', 'Admin\ModuleController::listCharts');
            $router->post('charts-update', '/charts', 'Admin\ModuleController::updateCharts');
            $router->get('lists', '/lists', 'Admin\ModuleController::listLists');
            $router->post('lists-update', '/lists', 'Admin\ModuleController::updateLists');
            $router->get('footers', '/footers', 'Admin\ModuleController::listFooters');
            $router->post('footers-update', '/footers', 'Admin\ModuleController::updateFooters');
            $router->get('history', '/history', 'Admin\ModuleController::listHistory');
            $router->post('history-update', '/history', 'Admin\ModuleController::updateHistory');
            $router->get('menus', '/menus', 'Admin\ModuleController::listMenus');
            $router->post('menus-update', '/menus', 'Admin\ModuleController::updateMenus');
            $router->get('languages', '/languages', 'Admin\ModuleController::listLanguages');
            $router->post('languages-update', '/languages', 'Admin\ModuleController::updateLanguages');
            $router->get('reports', '/reports', 'Admin\ModuleController::listReports');
            $router->post('reports-update', '/reports', 'Admin\ModuleController::updateReports');
            $router->get('sidebars', '/sidebars', 'Admin\ModuleController::listSidebars');
            $router->post('sidebars-update', '/sidebars', 'Admin\ModuleController::updateSidebars');
            $router->get('themes', '/themes', 'Admin\ModuleController::listThemes');
            $router->post('themes-update', '/themes', 'Admin\ModuleController::updateThemes');
            $router->get('tabs', '/tabs', 'Admin\ModuleController::listTabs');
            $router->post('tabs-update', '/tabs', 'Admin\ModuleController::updateTabs');
            $router->post('delete-module-settings', '/delete-module-settings', 'Admin\ModuleController::deleteModuleSettings');
            $router->get('map-data', '/map-data', 'Admin\LocationController::mapData');
            $router->get('map-data-edit', '/map-data-edit', 'Admin\LocationController::mapDataEdit');
            $router->post('map-data-update', '/map-data-edit', 'Admin\LocationController::mapDataSave');
            $router->post('map-data-delete', '/map-data-delete', 'Admin\LocationController::mapDataDelete');
            $router->get('locations-export', '/locations-export', 'Admin\LocationController::exportLocations');
            $router->get('locations-import', '/locations-import', 'Admin\LocationController::importLocations');
            $router->post('locations-import-action', '/locations-import', 'Admin\LocationController::importLocationsAction');
            $router->post('locations-import-from-tree', '/locations-import-from-tree', 'Admin\LocationController::importLocationsFromTree');
            $router->get('map-provider', '/map-provider', 'Admin\MapProviderController::mapProviderEdit');
            $router->post('map-provider-action', '/map-provider', 'Admin\MapProviderController::mapProviderSave');
            $router->get('admin-media', '/admin-media', 'Admin\MediaController::index');
            $router->get('admin-media-data', '/admin-media-data', 'Admin\MediaController::data');
            $router->post('admin-media-delete', '/admin-media-delete', 'Admin\MediaController::delete');
            $router->get('admin-media-upload', '/admin-media-upload', 'Admin\MediaController::upload');
            $router->post('admin-media-upload-action', '/admin-media-upload', 'Admin\MediaController::uploadAction');
            $router->get('upgrade', '/upgrade', 'Admin\UpgradeController::wizard');
            $router->post('upgrade-action', '/upgrade', 'Admin\UpgradeController::step');
            $router->get('admin-users', '/admin-users', 'Admin\UsersController::index');
            $router->get('admin-users-data', '/admin-users-data', 'Admin\UsersController::data');
            $router->get('admin-users-create', '/admin-users-create', 'Admin\UsersController::create');
            $router->post('admin-users-create-action', '/admin-users-create', 'Admin\UsersController::save');
            $router->get('admin-users-edit', '/admin-users-edit', 'Admin\UsersController::edit');
            $router->post('admin-users-update', '/admin-users-edit', 'Admin\UsersController::update');
            $router->get('admin-users-cleanup', '/admin-users-cleanup', 'Admin\UsersController::cleanup');
            $router->post('admin-users-cleanup-action', '/admin-users-cleanup', 'Admin\UsersController::cleanupAction');
            $router->get(CleanDataFolder::class, '/clean', CleanDataFolder::class);
            $router->post(DeletePath::class, '/delete-path', DeletePath::class);
            $router->get('admin-site-preferences', '/admin-site-preferences', 'AdminSiteController::preferencesForm');
            $router->post('admin-site-preferences-update', '/admin-site-preferences', 'AdminSiteController::preferencesSave');
            $router->get('admin-site-mail', '/admin-site-mail', 'AdminSiteController::mailForm');
            $router->post('admin-site-mail-update', '/admin-site-mail', 'AdminSiteController::mailSave');
            $router->get('admin-site-registration', '/admin-site-registration', 'AdminSiteController::registrationForm');
            $router->post('admin-site-registration-update', '/admin-site-registration', 'AdminSiteController::registrationSave');
            $router->get(BroadcastPage::class, '/broadcast', BroadcastPage::class);
            $router->post(BroadcastAction::class, '/broadcast', BroadcastAction::class);
            $router->get(PhpInformation::class, '/information', PhpInformation::class);
            $router->post('masquerade', '/masquerade/{user_id}', Masquerade::class);
            $router->get(SiteLogsPage::class, '/logs', SiteLogsPage::class);
            $router->post(SiteLogsAction::class, '/logs', SiteLogsAction::class);
            $router->get(SiteLogsData::class, '/logs-data', SiteLogsData::class);
            $router->post(SiteLogsDelete::class, '/logs-delete', SiteLogsDelete::class);
            $router->get(SiteLogsDownload::class, '/logs-download', SiteLogsDownload::class);
            $router->get(CreateTreePage::class, '/trees/create', CreateTreePage::class);
            $router->post(CreateTreeAction::class, '/trees/create', CreateTreeAction::class);
            $router->post(SelectDefaultTree::class, '/trees/default/{tree}', SelectDefaultTree::class);
            $router->get('tree-page-default-edit', '/trees/default-blocks', 'HomePageController::treePageDefaultEdit');
            $router->post('tree-page-default-update', '/trees/default-blocks', 'HomePageController::treePageDefaultUpdate');
            $router->post(DeleteTreeAction::class, '/trees/delete/{tree}', DeleteTreeAction::class);
            $router->get('admin-trees-merge', '/trees/merge', 'AdminTreesController::merge');
            $router->post('admin-trees-merge-action', '/trees/merge', 'AdminTreesController::mergeAction');
            $router->post('admin-trees-sync', '/trees/sync', 'AdminTreesController::synchronize');
            $router->get('unused-media-thumbnail', '/unused-media-thumbnail', 'MediaFileController::unusedMediaThumbnail');
            $router->post('delete-user', '/users/delete/{user_id}', DeleteUser::class);
            $router->get('user-page-default-edit', '/user-page-default-edit', 'HomePageController::userPageDefaultEdit');
            $router->post('user-page-default-update', '/user-page-default-update', 'HomePageController::userPageDefaultUpdate');
        });

        // Manager routes (without a tree).
        $router->attach('', '/admin', static function (Map $router) {
            $router->extras([
                'middleware' => [AuthManager::class]
            ]);

            $router->get('manage-trees', '/trees/manage{/tree}', 'AdminTreesController::index');
        });

        // Manager routes.
        $router->attach('', '/tree/{tree}', static function (Map $router) {
            $router->extras([
                'middleware' => [AuthManager::class]
            ]);

            $router->get(PendingChangesLogPage::class, '/changes-log', PendingChangesLogPage::class);
            $router->post(PendingChangesLogAction::class, '/changes-log', PendingChangesLogAction::class);
            $router->get(PendingChangesLogData::class, '/changes-data', PendingChangesLogData::class);
            $router->post(PendingChangesLogDelete::class, '/changes-delete', PendingChangesLogDelete::class);
            $router->get(PendingChangesLogDownload::class, '/changes-download', PendingChangesLogDownload::class);
            $router->get('admin-trees-check', '/check', 'AdminTreesController::check');
            $router->get('admin-trees-duplicates', '/duplicates', 'AdminTreesController::duplicates');
            $router->get(ExportGedcomPage::class, '/export', ExportGedcomPage::class);
            $router->post(ExportGedcomClient::class, '/export-client', ExportGedcomClient::class);
            $router->post(ExportGedcomServer::class, '/export-server', ExportGedcomServer::class);
            $router->get('admin-trees-import', '/import', 'AdminTreesController::importForm');
            $router->post('admin-trees-import-action', '/import', 'AdminTreesController::importAction');
            $router->get('admin-trees-places', '/places', 'AdminTreesController::places');
            $router->post('admin-trees-places-action', '/places', 'AdminTreesController::placesAction');
            $router->get('admin-trees-preferences', '/preferences', 'AdminTreesController::preferences');
            $router->post('admin-trees-preferences-update', '/preferences', 'AdminTreesController::preferencesUpdate');
            $router->get('admin-trees-renumber', '/renumber', 'AdminTreesController::renumber');
            $router->post('admin-trees-renumber-action', '/renumber', 'AdminTreesController::renumberAction');
            $router->get('admin-trees-unconnected', '/aunconnected', 'AdminTreesController::unconnected');
            $router->get('tree-page-edit', '/tree-page-edit', 'HomePageController::treePageEdit');
            $router->post('import', '/load', 'GedcomFileController::import');
            $router->post('tree-page-update', '/tree-page-update', 'HomePageController::treePageUpdate');
            $router->get('merge-records', '/merge-records', 'AdminController::mergeRecords');
            $router->post('merge-records-update', '/merge-records', 'AdminController::mergeRecordsAction');
            $router->get('tree-page-block-edit', '/tree-page-block-edit', 'HomePageController::treePageBlockEdit');
            $router->post('tree-page-block-update', '/tree-page-block-edit', 'HomePageController::treePageBlockUpdate');
            $router->get('tree-preferences', '/preferences', 'AdminController::treePreferencesEdit');
            $router->post('tree-preferences-update', '/preferences', 'AdminController::treePreferencesUpdate');
            $router->get('tree-privacy', '/privacy', 'AdminController::treePrivacyEdit');
            $router->post('tree-privacy-update', '/privacy', 'AdminController::treePrivacyUpdate');
        });

        // Moderator routes.
        $router->attach('', '/tree/{tree}', static function (Map $router) {
            $router->extras([
                'middleware' => [AuthModerator::class]
            ]);
            $router->post(PendingChangesAcceptTree::class, '/accept', PendingChangesAcceptTree::class);
            $router->post(PendingChangesAcceptRecord::class, '/accept/{xref}', PendingChangesAcceptRecord::class);
            $router->post(PendingChangesAcceptChange::class, '/accept/{xref}/{change}', PendingChangesAcceptChange::class);
            $router->get(PendingChanges::class, '/pending', PendingChanges::class);
            $router->post(PendingChangesRejectTree::class, '/reject', PendingChangesRejectTree::class);
            $router->post(PendingChangesRejectRecord::class, '/reject/{xref}', PendingChangesRejectRecord::class);
            $router->post(PendingChangesRejectChange::class, '/reject/{xref}/{change}', PendingChangesRejectChange::class);
        });

        // Editor routes.
        $router->attach('', '/tree/{tree}', static function (Map $router) {
            $router->extras([
                'middleware' => [AuthEditor::class]
            ]);

            $router->get('add-child-to-family', '/add-child-to-family', 'EditFamilyController::addChild');
            $router->post('add-child-to-family-action', '/add-child-to-family', 'EditFamilyController::addChildAction');
            $router->get(AddNewFact::class, '/add-fact/{xref}/{fact}', AddNewFact::class);
            $router->post(SelectNewFact::class, '/add-fact/{xref}', SelectNewFact::class);
            $router->get('add-media-file', '/add-media-file', 'EditMediaController::addMediaFile');
            $router->post('add-media-file-update', '/add-media-file', 'EditMediaController::addMediaFileAction');
            $router->get('add-name', '/add-name', 'EditIndividualController::addName');
            $router->post('add-name-action', '/add-name-update/{xref}', 'EditIndividualController::addNameAction');
            $router->get('add-spouse-to-family', '/add-spouse-to-family', 'EditFamilyController::addSpouse');
            $router->post('add-spouse-to-family-action', '/add-spouse-to-family', 'EditFamilyController::addSpouseAction');
            $router->get('change-family-members', '/change-family-members', 'EditFamilyController::changeFamilyMembers');
            $router->post('change-family-members-action', '/change-family-members', 'EditFamilyController::changeFamilyMembersAction');
            $router->get(CreateMediaObjectModal::class, '/create-media-object', CreateMediaObjectModal::class);
            $router->post(CreateMediaObjectAction::class, '/create-media-object', CreateMediaObjectAction::class);
            $router->post('create-media-from-file', '/create-media-from-file', 'EditMediaController::createMediaObjectFromFileAction');
            $router->post(CopyFact::class, '/copy/{xref}/{fact_id}', CopyFact::class);
            $router->get(CreateNoteModal::class, '/create-note-object', CreateNoteModal::class);
            $router->post(CreateNoteAction::class, '/create-note-object', CreateNoteAction::class);
            $router->get(CreateRepositoryModal::class, '/create-repository', CreateRepositoryModal::class);
            $router->post(CreateRepositoryAction::class, '/create-repository', CreateRepositoryAction::class);
            $router->get(CreateSourceModal::class, '/create-source', CreateSourceModal::class);
            $router->post(CreateSourceAction::class, '/create-source', CreateSourceAction::class);
            $router->get(CreateSubmitterModal::class, '/create-submitter', CreateSubmitterModal::class);
            $router->post(CreateSubmitterAction::class, '/create-submitter', CreateSubmitterAction::class);
            $router->post(DeleteRecord::class, '/delete/{xref}', DeleteRecord::class);
            $router->post(DeleteFact::class, '/delete/{xref}/{fact_id}', DeleteFact::class);
            $router->get(EditFact::class, '/edit-fact', EditFact::class);
            $router->get('edit-media-file', '/edit-media-file', 'EditMediaController::editMediaFile');
            $router->post('edit-media-file-update', '/edit-media-file', 'EditMediaController::editMediaFileAction');
            $router->get('edit-note-object', '/edit-note-object', 'EditNoteController::editNoteObject');
            $router->post('edit-note-object-action', '/edit-note-object', 'EditNoteController::updateNoteObject');
            $router->get(EditRawFactPage::class, '/edit-raw/{xref}/{fact_id}', EditRawFactPage::class);
            $router->post(EditRawFactAction::class, '/edit-raw/{xref}/{fact_id}', EditRawFactAction::class);
            $router->get(EditRawRecordPage::class, '/edit-raw/{xref}', EditRawRecordPage::class);
            $router->post(EditRawRecordAction::class, '/edit-raw/{xref}', EditRawRecordAction::class);
            $router->get('link-media-to-individual', '/link-media-to-individual', 'EditMediaController::linkMediaToIndividual');
            $router->get('link-media-to-family', '/link-media-to-family', 'EditMediaController::linkMediaToFamily');
            $router->get('link-media-to-source', '/link-media-to-source', 'EditMediaController::linkMediaToSource');
            $router->post('link-media-to-record', '/link-media-to-record', 'EditMediaController::linkMediaToRecordAction');
            $router->post(PasteFact::class, '/paste-fact/{xref}', PasteFact::class);
            $router->get(ReorderChildrenPage::class, '/reorder-children/{xref}', ReorderChildrenPage::class);
            $router->post(ReorderChildrenAction::class, '/reorder-children/{xref}', ReorderChildrenAction::class);
            $router->get(ReorderMediaPage::class, '/reorder-media/{xref}', ReorderMediaPage::class);
            $router->post(ReorderMediaAction::class, '/reorder-media/{xref}', ReorderMediaAction::class);
            $router->get(ReorderNamesPage::class, '/reorder-names/{xref}', ReorderNamesPage::class);
            $router->post(ReorderNamesAction::class, '/reorder-names/{xref}', ReorderNamesAction::class);
            $router->get(ReorderSpousesPage::class, '/reorder-spouses/{xref}', ReorderSpousesPage::class);
            $router->post(ReorderSpousesAction::class, '/reorder-spouses/{xref}', ReorderSpousesAction::class);
            $router->get(SearchReplacePage::class, '/search-replace', SearchReplacePage::class);
            $router->post(SearchReplaceAction::class, '/search-replace', SearchReplaceAction::class);
            $router->get('add-child-to-individual', '/add-child-to-individual', 'EditIndividualController::addChild');
            $router->post('add-child-to-individual-action', '/add-child-to-individual', 'EditIndividualController::addChildAction');
            $router->get('add-parent-to-individual', '/add-parent-to-individual', 'EditIndividualController::addParent');
            $router->post('add-parent-to-individual-action', '/add-parent-to-individual', 'EditIndividualController::addParentAction');
            $router->get('add-spouse-to-individual', '/add-spouse-to-individual', 'EditIndividualController::addSpouse');
            $router->post('add-spouse-to-individual-action', '/add-spouse-to-individual', 'EditIndividualController::addSpouseAction');
            $router->get('add-unlinked-individual', '/add-unlinked-individual', 'EditIndividualController::addUnlinked');
            $router->post('add-unlinked-individual-action', '/add-unlinked-individual', 'EditIndividualController::addUnlinkedAction');
            $router->get('link-child-to-family', '/link-child-to-family', 'EditIndividualController::linkChildToFamily');
            $router->post('link-child-to-family-action', '/link-child-to-family', 'EditIndividualController::linkChildToFamilyAction');
            $router->get('link-spouse-to-individual', '/link-spouse-to-individual', 'EditIndividualController::linkSpouseToIndividual');
            $router->post('link-spouse-to-individual-action', '/link-spouse-to-individual', 'EditIndividualController::linkSpouseToIndividualAction');
            $router->get('edit-name', '/edit-name/{xref}/{fact_id}', 'EditIndividualController::editName');
            $router->post('edit-name-action', '/edit-name-update/{xref}/{fact_id}', 'EditIndividualController::editNameAction');
            $router->post('update-fact', '/update-fact/{xref}{/fact_id}', 'EditGedcomRecordController::updateFact');
        });

        // Member routes.
        $router->attach('', '/tree/{tree}', static function (Map $router) {
            $router->extras([
                'middleware' => [AuthMember::class]
            ]);

            $router->get('user-page', '/my-page', 'HomePageController::userPage');
            $router->get('user-page-block', '/my-page-block', 'HomePageController::userPageBlock');
            $router->get('user-page-edit', '/my-page-edit', 'HomePageController::userPageEdit');
            $router->post('user-page-update', '/my-page-edit', 'HomePageController::userPageUpdate');
            $router->get('user-page-block-edit', '/my-page-block-edit', 'HomePageController::userPageBlockEdit');
            $router->post('user-page-block-update', '/my-page-block-edit', 'HomePageController::userPageBlockUpdate');
        });

        // User routes.
        $router->attach('', '', static function (Map $router) {
            $router->extras([
                'middleware' => [AuthLoggedIn::class]
            ]);

            $router->get(AccountEdit::class, '/my-account{/tree}', AccountEdit::class);
            $router->post(AccountUpdate::class, '/my-account{/tree}', AccountUpdate::class);
            $router->post(AccountDelete::class, '/my-account-delete', AccountDelete::class);
        });

        // Visitor routes.
        $router->attach('', '', static function (Map $router) {
            $router->extras([
                'middleware' => [AuthVisitor::class]
            ]);

            $router->get(PasswordRequestPage::class, '/password-request', PasswordRequestPage::class);
            $router->post(PasswordRequestAction::class, '/password-request', PasswordRequestAction::class);
            $router->get(RegisterPage::class, '/register', RegisterPage::class);
            $router->post(RegisterAction::class, '/register', RegisterAction::class);
        });

        // Public routes.
        $router->attach('', '/tree/{tree}', static function (Map $router) {
            $router->get('tree-page', '', 'HomePageController::treePage');
            $router->get('autocomplete-folder', '/autocomplete-folder', 'AutocompleteController::folder');
            $router->get('autocomplete-page', '/autocomplete-page', 'AutocompleteController::page');
            $router->get('autocomplete-place', '/autocomplete-place', 'AutocompleteController::place');
            $router->get('calendar', '/calendar/{view}', 'CalendarController::page');
            $router->get('calendar-events', '/calendar-events/{view}', 'CalendarController::calendar');
            $router->get(ContactPage::class, '/contact', ContactPage::class);
            $router->post(ContactAction::class, '/contact', ContactAction::class);
            $router->get(FamilyPage::class, '/family/{xref}{/slug}', FamilyPage::class);
            $router->get(IndividualPage::class, '/individual/{xref}{/slug}', IndividualPage::class);
            $router->get('media-thumbnail', '/media-thumbnail', 'MediaFileController::mediaThumbnail');
            $router->get('media-download', '/media-download', 'MediaFileController::mediaDownload');
            $router->get(MediaPage::class, '/media/{xref}{/slug}', MediaPage::class);
            $router->post(MessageSelect::class, '/message-select', MessageSelect::class);
            $router->get(MessagePage::class, '/message-compose', MessagePage::class);
            $router->post(MessageAction::class, '/message-send', MessageAction::class);
            $router->get(NotePage::class, '/note/{xref}{/slug}', NotePage::class);
            $router->get(GedcomRecordPage::class, '/record/{xref}{/slug}', GedcomRecordPage::class);
            $router->get(RepositoryPage::class, '/repository/{xref}{/slug}', RepositoryPage::class);
            $router->get(ReportListPage::class, '/report', ReportListPage::class);
            $router->post(ReportListAction::class, '/report', ReportListAction::class);
            $router->get(ReportSetupPage::class, '/report/{report}', ReportSetupPage::class);
            $router->post(ReportSetupAction::class, '/report/{report}', ReportSetupAction::class);
            $router->get(ReportGenerate::class, '/report-run/{report}', ReportGenerate::class);
            $router->get(SearchAdvancedPage::class, '/search-advanced', SearchAdvancedPage::class);
            $router->post(SearchAdvancedAction::class, '/search-advanced', SearchAdvancedAction::class);
            $router->get(SearchGeneralPage::class, '/search-general', SearchGeneralPage::class);
            $router->post(SearchGeneralAction::class, '/search-general', SearchGeneralAction::class);
            $router->get(SearchPhoneticPage::class, '/search-phonetic', SearchPhoneticPage::class);
            $router->post(SearchPhoneticAction::class, '/search-phonetic', SearchPhoneticAction::class);
            $router->post(SearchQuickAction::class, '/search-quick', SearchQuickAction::class);
            $router->post(Select2Family::class, '/select2-family', Select2Family::class);
            $router->post(Select2Individual::class, '/select2-individual', Select2Individual::class);
            $router->post(Select2MediaObject::class, '/select2-media', Select2MediaObject::class);
            $router->post(Select2Note::class, '/select2-note', Select2Note::class);
            $router->post(Select2Source::class, '/select2-source', Select2Source::class);
            $router->post(Select2Submitter::class, '/select2-submitter', Select2Submitter::class);
            $router->post(Select2Repository::class, '/select2-repository', Select2Repository::class);
            $router->get(SourcePage::class, '/source/{xref}{/slug}', SourcePage::class);
            $router->get('tree-page-block', '/tree-page-block', 'HomePageController::treePageBlock');
            $router->get('example', '/…');
        });

        $router->get('module', '/module/{module}/{action}{/tree}', ModuleAction::class)
            ->allows(RequestMethodInterface::METHOD_POST);

        $router->get(HelpText::class, '/help/{topic}', HelpText::class);
        $router->post(SelectLanguage::class, '/language/{language}', SelectLanguage::class);
        $router->get(LoginPage::class, '/login{/tree}', LoginPage::class);
        $router->post(LoginAction::class, '/login{/tree}', LoginAction::class);
        $router->post(Logout::class, '/logout', Logout::class);
        $router->get(PasswordResetPage::class, '/password-reset', PasswordResetPage::class);
        $router->post(PasswordResetAction::class, '/password-reset', PasswordResetAction::class);
        $router->get(Ping::class, '/ping', Ping::class);
        $router->get(RobotsTxt::class, '/robots.txt', RobotsTxt::class);
        $router->post(SelectTheme::class, '/theme/{theme}', SelectTheme::class);
        $router->get(VerifyEmail::class, '/verify', VerifyEmail::class);
        $router->get(PrivacyPolicy::class, '/privacy-policy', PrivacyPolicy::class);
        $router->get(HomePage::class, '/', HomePage::class);

        // Legacy URLs from older software.
        $router->get(RedirectFamilyPhp::class, '/family.php', RedirectFamilyPhp::class);
        $router->get(RedirectGedRecordPhp::class, '/gedrecord.php', RedirectGedRecordPhp::class);
        $router->get(RedirectIndividualPhp::class, '/individual.php', RedirectIndividualPhp::class);
        $router->get(RedirectMediaViewerPhp::class, '/mediaviewer.php', RedirectMediaViewerPhp::class);
        $router->get(RedirectNotePhp::class, '/note.php', RedirectNotePhp::class);
        $router->get(RedirectRepoPhp::class, '/repository.php', RedirectRepoPhp::class);
        $router->get(RedirectSourcePhp::class, '/source.php', RedirectSourcePhp::class);
    }
}
