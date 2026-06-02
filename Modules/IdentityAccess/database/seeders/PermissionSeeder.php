<?php

namespace Modules\IdentityAccess\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ─── 1. Define all permissions ────────────────────────────────────────
        $permissions = [

            // ─── IdentityAccess ───────────────────────────────────────────────
            'identityaccess.users.view',
            'identityaccess.users.create',
            'identityaccess.users.edit',
            'identityaccess.users.delete',
            'identityaccess.users.impersonate',

            'identityaccess.roles.view',
            'identityaccess.roles.assign',
            'identityaccess.roles.manage',

            'identityaccess.permissions.view',
            'identityaccess.permissions.manage',

            // ─── Students ────────────────────────────────────────────────────
            'students.profile.view_own',
            'students.profile.edit_own',
            'students.profile.view_any',
            'students.profile.edit_any',

            'students.documents.upload',
            'students.documents.view_own',
            'students.documents.view_any',
            'students.documents.delete_own',
            'students.documents.delete_any',

            // ─── Teams ───────────────────────────────────────────────────────
            'teams.view_own',
            'teams.view_any',
            'teams.create',
            'teams.edit_own',
            'teams.edit_any',
            'teams.delete_own',
            'teams.delete_any',

            'teams.members.invite',
            'teams.members.remove',
            'teams.members.change_role',
            'teams.members.view',

            // ─── Programs ────────────────────────────────────────────────────
            'programs.view',
            'programs.create',
            'programs.edit',
            'programs.delete',
            'programs.publish',
            'programs.manage_rounds',

            // ─── Applications ────────────────────────────────────────────────
            'applications.view_own',
            'applications.view_any',
            'applications.create',
            'applications.edit_own',
            'applications.edit_any',
            'applications.delete_own',
            'applications.delete_any',
            'applications.submit',
            'applications.withdraw',
            'applications.change_status',
            'applications.export',

            // ─── Evaluation ──────────────────────────────────────────────────
            'evaluation.view_own',
            'evaluation.view_any',
            'evaluation.create',
            'evaluation.edit_own',
            'evaluation.edit_any',
            'evaluation.submit',
            'evaluation.approve',
            'evaluation.reject',
            'evaluation.manage_criteria',
            'evaluation.export',

            // ─── Mentorship ──────────────────────────────────────────────────
            'mentorship.view_own',
            'mentorship.view_any',
            'mentorship.request',
            'mentorship.assign',
            'mentorship.edit_own',
            'mentorship.edit_any',
            'mentorship.sessions.log',
            'mentorship.sessions.view_own',
            'mentorship.sessions.view_any',
            'mentorship.sessions.approve',

            // ─── Organizations ───────────────────────────────────────────────
            'organizations.view',
            'organizations.create',
            'organizations.edit_own',
            'organizations.edit_any',
            'organizations.delete',
            'organizations.manage_contacts',

            // ─── Reporting ───────────────────────────────────────────────────
            'reporting.view_basic',
            'reporting.view_advanced',
            'reporting.export_csv',
            'reporting.export_xlsx',
            'reporting.export_pdf',
            'reporting.manage_templates',

            // ─── Notifications ───────────────────────────────────────────────
            'notifications.view_own',
            'notifications.manage_own',
            'notifications.send',
            'notifications.manage_templates',
            'notifications.view_log',

            // ─── Content (CMS) ───────────────────────────────────────────────
            'content.view',
            'content.create',
            'content.edit_own',
            'content.edit_any',
            'content.delete_own',
            'content.delete_any',
            'content.publish',
            'content.manage_media',

            // ─── AuditCompliance ─────────────────────────────────────────────
            'audit.view',
            'audit.export',
            'audit.manage_gdpr',
            'audit.anonymize_users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ─── 2. Assign permissions to roles ───────────────────────────────────
        $map = [

            // ─── guest ───────────────────────────────────────────────────────
            'guest' => [
                'programs.view',
                'content.view',
                'notifications.view_own',
                'notifications.manage_own',
            ],

            // ─── student ─────────────────────────────────────────────────────
            'student' => [
                'students.profile.view_own',
                'students.profile.edit_own',
                'students.documents.upload',
                'students.documents.view_own',
                'students.documents.delete_own',
                'teams.view_own',
                'teams.create',
                'teams.edit_own',
                'teams.delete_own',
                'teams.members.view',
                'programs.view',
                'applications.view_own',
                'applications.create',
                'applications.edit_own',
                'applications.delete_own',
                'applications.submit',
                'applications.withdraw',
                'mentorship.view_own',
                'mentorship.request',
                'mentorship.edit_own',
                'mentorship.sessions.log',
                'mentorship.sessions.view_own',
                'notifications.view_own',
                'notifications.manage_own',
                'content.view',
            ],

            // ─── team_leader ─────────────────────────────────────────────────
            'team_leader' => [
                'students.profile.view_own',
                'students.profile.edit_own',
                'students.documents.upload',
                'students.documents.view_own',
                'students.documents.delete_own',
                'teams.view_own',
                'teams.create',
                'teams.edit_own',
                'teams.delete_own',
                'teams.members.view',
                'teams.members.invite',
                'teams.members.remove',
                'teams.members.change_role',
                'programs.view',
                'applications.view_own',
                'applications.create',
                'applications.edit_own',
                'applications.delete_own',
                'applications.submit',
                'applications.withdraw',
                'mentorship.view_own',
                'mentorship.request',
                'mentorship.edit_own',
                'mentorship.sessions.log',
                'mentorship.sessions.view_own',
                'notifications.view_own',
                'notifications.manage_own',
                'content.view',
            ],

            // ─── partner ─────────────────────────────────────────────────────
            'partner' => [
                'programs.view',
                'applications.view_own',
                'organizations.view',
                'organizations.create',
                'organizations.edit_own',
                'organizations.manage_contacts',
                'notifications.view_own',
                'notifications.manage_own',
                'content.view',
            ],

            // ─── organization (alias of partner) ───────────────────────────
            'organization' => [
                'programs.view',
                'applications.view_own',
                'organizations.view',
                'organizations.create',
                'organizations.edit_own',
                'organizations.manage_contacts',
                'notifications.view_own',
                'notifications.manage_own',
                'content.view',
            ],

            // ─── mentor ──────────────────────────────────────────────────────
            'mentor' => [
                'students.profile.view_own',
                'programs.view',
                'mentorship.view_own',
                'mentorship.view_any',
                'mentorship.assign',
                'mentorship.edit_own',
                'mentorship.sessions.log',
                'mentorship.sessions.view_own',
                'mentorship.sessions.view_any',
                'mentorship.sessions.approve',
                'notifications.view_own',
                'notifications.manage_own',
                'content.view',
            ],

            // ─── evaluator ───────────────────────────────────────────────────
            'evaluator' => [
                'students.profile.view_own',
                'students.documents.view_own',
                'programs.view',
                'applications.view_any',
                'evaluation.view_own',
                'evaluation.view_any',
                'evaluation.create',
                'evaluation.edit_own',
                'evaluation.submit',
                'evaluation.approve',
                'evaluation.reject',
                'evaluation.export',
                'notifications.view_own',
                'notifications.manage_own',
                'content.view',
            ],

            'predseda_komisie' => [
                'programs.view',
                'applications.view_any',
                'applications.change_status',
                'evaluation.view_any',
                'notifications.view_own',
                'notifications.manage_own',
                'content.view',
            ],

            // ─── cms_editor ──────────────────────────────────────────────────
            'cms_editor' => [
                'content.view',
                'content.create',
                'content.edit_own',
                'content.edit_any',
                'content.delete_own',
                'content.delete_any',
                'content.publish',
                'content.manage_media',
                'notifications.view_own',
                'notifications.manage_own',
                'notifications.send',
                'notifications.manage_templates',
            ],

            // ─── content-manager (alias of cms_editor) ─────────────────────
            'content-manager' => [
                'content.view',
                'content.create',
                'content.edit_own',
                'content.edit_any',
                'content.delete_own',
                'content.delete_any',
                'content.publish',
                'content.manage_media',
                'notifications.view_own',
                'notifications.manage_own',
                'notifications.send',
                'notifications.manage_templates',
            ],

            // ─── nti_admin ───────────────────────────────────────────────────
            'nti_admin' => [
                'identityaccess.users.view',
                'identityaccess.users.create',
                'identityaccess.users.edit',
                'identityaccess.roles.view',
                'identityaccess.roles.assign',
                'identityaccess.permissions.view',
                'students.profile.view_own',
                'students.profile.edit_own',
                'students.profile.view_any',
                'students.profile.edit_any',
                'students.documents.upload',
                'students.documents.view_own',
                'students.documents.view_any',
                'students.documents.delete_own',
                'students.documents.delete_any',
                'teams.view_own',
                'teams.view_any',
                'teams.create',
                'teams.edit_own',
                'teams.edit_any',
                'teams.delete_own',
                'teams.delete_any',
                'teams.members.invite',
                'teams.members.remove',
                'teams.members.change_role',
                'teams.members.view',
                'programs.view',
                'programs.create',
                'programs.edit',
                'programs.delete',
                'programs.publish',
                'programs.manage_rounds',
                'applications.view_own',
                'applications.view_any',
                'applications.create',
                'applications.edit_own',
                'applications.edit_any',
                'applications.delete_own',
                'applications.delete_any',
                'applications.submit',
                'applications.withdraw',
                'applications.change_status',
                'applications.export',
                'evaluation.view_own',
                'evaluation.view_any',
                'evaluation.create',
                'evaluation.edit_own',
                'evaluation.edit_any',
                'evaluation.submit',
                'evaluation.approve',
                'evaluation.reject',
                'evaluation.manage_criteria',
                'evaluation.export',
                'mentorship.view_own',
                'mentorship.view_any',
                'mentorship.request',
                'mentorship.assign',
                'mentorship.edit_own',
                'mentorship.edit_any',
                'mentorship.sessions.log',
                'mentorship.sessions.view_own',
                'mentorship.sessions.view_any',
                'mentorship.sessions.approve',
                'organizations.view',
                'organizations.create',
                'organizations.edit_own',
                'organizations.edit_any',
                'organizations.delete',
                'organizations.manage_contacts',
                'reporting.view_basic',
                'reporting.view_advanced',
                'reporting.export_csv',
                'reporting.export_xlsx',
                'reporting.export_pdf',
                'reporting.manage_templates',
                'notifications.view_own',
                'notifications.manage_own',
                'notifications.send',
                'notifications.manage_templates',
                'notifications.view_log',
                'content.view',
                'content.create',
                'content.edit_own',
                'content.edit_any',
                'content.delete_own',
                'content.delete_any',
                'content.publish',
                'content.manage_media',
                'audit.view',
                'audit.export',
            ],

            // ─── nti_superadmin ──────────────────────────────────────────────
            // Gets every permission — just pluck all from DB.
            'nti_superadmin' => null,
        ];

        foreach ($map as $roleName => $rolePermissions) {
            $role = Role::where('name', $roleName)->firstOrFail();

            $permissionIds = $rolePermissions === null
                ? Permission::pluck('id')                              // superadmin gets all
                : Permission::whereIn('name', $rolePermissions)->pluck('id');

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
