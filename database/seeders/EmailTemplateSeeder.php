<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    public function run()
    {
        $templates = [
            // System Templates
            [
                'name' => 'temporary_password',
                'subject' => 'Your PFE Manager Account Details',
                'content' => "Hello {name},\n\n".
                            "Your account has been created in the PFE Management System.\n".
                            "Your temporary password is: {temporary_password}\n\n".
                            "Please log in and change your password as soon as possible.\n".
                            "This temporary password will expire in {expiry_days} days.\n\n".
                            "Best regards,\nPFE Management System",
                'description' => 'Template for sending temporary passwords to imported users',
                'placeholders' => ['name', 'temporary_password', 'expiry_days'],
                'type' => 'System',
                'language' => 'English',
                'is_active' => true
            ],

            // Project Proposal Period Templates
            [
                'name' => 'project_proposal_period_start',
                'subject' => 'PFE Project Proposal Period Now Open',
                'content' => "Dear {name},\n\n".
                            "The project proposal period for {academic_year} is now open.\n\n".
                            "Start Date: {start_date}\n".
                            "Deadline: {end_date}\n\n".
                            "You can submit your project proposals through the PFE Management Platform.\n\n".
                            "Best regards,\nPFE Management System",
                'description' => 'Notification for the start of project proposal period',
                'placeholders' => ['name', 'academic_year', 'start_date', 'end_date'],
                'type' => 'Notification',
                'language' => 'English',
                'is_active' => true
            ],

            [
                'name' => 'project_proposal_period_end',
                'subject' => 'PFE Project Proposal Period Closed',
                'content' => "Dear {name},\n\n".
                            "The project proposal period for {academic_year} is now closed.\n\n".
                            "Total proposals received: {proposal_count}\n".
                            "Next steps: The proposals will be reviewed by the department heads.\n\n".
                            "You will be notified of the results soon.\n\n".
                            "Best regards,\nPFE Management System",
                'description' => 'Notification for the end of project proposal period',
                'placeholders' => ['name', 'academic_year', 'proposal_count'],
                'type' => 'Notification',
                'language' => 'English',
                'is_active' => true
            ],

            // Reminder Templates
            [
                'name' => 'proposal_deadline_reminder',
                'subject' => 'Reminder: PFE Project Proposal Deadline Approaching',
                'content' => "Dear {name},\n\n".
                            "This is a reminder that the deadline for submitting PFE project proposals is approaching.\n\n".
                            "Deadline: {deadline}\n".
                            "Days remaining: {days_remaining}\n\n".
                            "If you haven't submitted your proposal yet, please do so as soon as possible.\n\n".
                            "Best regards,\nPFE Management System",
                'description' => 'Reminder for project proposal deadline',
                'placeholders' => ['name', 'deadline', 'days_remaining'],
                'type' => 'Reminder',
                'language' => 'English',
                'is_active' => true
            ],

            // French versions
            [
                'name' => 'temporary_password_fr',
                'subject' => 'Vos identifiants PFE Manager',
                'content' => "Bonjour {name},\n\n".
                            "Votre compte a été créé dans le système de gestion PFE.\n".
                            "Votre mot de passe temporaire est : {temporary_password}\n\n".
                            "Veuillez vous connecter et changer votre mot de passe dès que possible.\n".
                            "Ce mot de passe temporaire expirera dans {expiry_days} jours.\n\n".
                            "Cordialement,\nSystème de Gestion PFE",
                'description' => 'Modèle pour l\'envoi des mots de passe temporaires',
                'placeholders' => ['name', 'temporary_password', 'expiry_days'],
                'type' => 'System',
                'language' => 'French',
                'is_active' => true
            ],
            
            [
                'name' => 'project_proposal_period_start_fr',
                'subject' => 'Période de proposition de PFE maintenant ouverte',
                'content' => "Cher/Chère {name},\n\n".
                            "La période de proposition de projets PFE pour {academic_year} est maintenant ouverte.\n\n".
                            "Date de début : {start_date}\n".
                            "Date limite : {end_date}\n\n".
                            "Vous pouvez soumettre vos propositions de projets via la plateforme de gestion PFE.\n\n".
                            "Cordialement,\nSystème de Gestion PFE",
                'description' => 'Notification de début de période de proposition de projets',
                'placeholders' => ['name', 'academic_year', 'start_date', 'end_date'],
                'type' => 'Notification',
                'language' => 'French',
                'is_active' => true
            ]
        ];

        foreach ($templates as $template) {
            EmailTemplate::create($template);
        }
    }
}
