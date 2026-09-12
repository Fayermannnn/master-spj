<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

/**
 * Permission list bertambah per fase. Jangan menambah permission untuk
 * modul yang belum dibangun — lihat PROJECT_BLUEPRINT.md §9 roadmap fase.
 */
enum PermissionName: string
{
    case OrganizationsViewAny = 'organizations.viewAny';
    case OrganizationsView = 'organizations.view';
    case OrganizationsCreate = 'organizations.create';
    case OrganizationsUpdate = 'organizations.update';
    case OrganizationsDelete = 'organizations.delete';

    case UsersViewAny = 'users.viewAny';
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';

    case ProjectTypesManage = 'project_types.manage';

    case ClientsViewAny = 'clients.viewAny';
    case ClientsView = 'clients.view';
    case ClientsCreate = 'clients.create';
    case ClientsUpdate = 'clients.update';
    case ClientsDelete = 'clients.delete';

    case ProjectsViewAny = 'projects.viewAny';
    case ProjectsView = 'projects.view';
    case ProjectsCreate = 'projects.create';
    case ProjectsUpdate = 'projects.update';
    case ProjectsDelete = 'projects.delete';
    case ProjectsTransitionStatus = 'projects.transitionStatus';

    case PersonnelCategoriesManage = 'personnel_categories.manage';

    case PersonnelViewAny = 'personnel.viewAny';
    case PersonnelView = 'personnel.view';
    case PersonnelCreate = 'personnel.create';
    case PersonnelUpdate = 'personnel.update';
    case PersonnelDelete = 'personnel.delete';

    case PersonnelAssignmentsManage = 'personnel_assignments.manage';

    case TaxTypesManage = 'tax_types.manage';
    case CostCategoriesManage = 'cost_categories.manage';
}
