<?php

namespace Database\Seeders;

use App\Models\Research;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Create default admin ────────────────────────────────────────
        $admin = User::create([
            'name'     => 'Administrator',
            'email'    => 'admin@uberepository.edu',
            'password' => Hash::make('Admin@1234'),
            'role'     => 'admin',
        ]);

        // ── Create sample researchers ───────────────────────────────────
        $researchers = [
            ['name' => 'Dr. Juan dela Cruz',    'email' => 'jdelacruz@philcst.edu.ph',  'department' => 'College of Computer Studies'],
            ['name' => 'Prof. Maria Santos',    'email' => 'msantos@philcst.edu.ph',    'department' => 'College of Engineering and Architecture'],
            ['name' => 'Dr. Ahmed Hassan',      'email' => 'ahassan@philcst.edu.ph',    'department' => 'College of Business and Management'],
            ['name' => 'Prof. Ana Reyes',       'email' => 'areyes@philcst.edu.ph',     'department' => 'College of Criminal Justice Education'],
            ['name' => 'Dr. Carlo Bautista',    'email' => 'cbautista@philcst.edu.ph',  'department' => 'College of Maritime Studies'],
        ];

        $createdResearchers = [];
        foreach ($researchers as $r) {
            $createdResearchers[] = User::create([
                'name'       => $r['name'],
                'email'      => $r['email'],
                'password'   => Hash::make('Password@123'),
                'role'       => 'researcher',
                'department' => $r['department'],
            ]);
        }

        // ── Create sample research ──────────────────────────────────────
        $samples = [
            [
                'title'          => 'Machine Learning Applications in Criminal Investigation: A Philippine Context',
                'submission_category' => 'research',
                'type'           => 'Thesis',
                'author_name'    => 'Dr. Juan dela Cruz',
                'department'     => 'College of Computer Studies',
                'year_published' => 2024,
                'keywords'       => 'machine learning, criminal justice, Philippines, AI, forensics',
                'abstract'       => 'This study explores the application of machine learning techniques in criminal investigation processes within the Philippine law enforcement context. The research examines how AI-powered tools can enhance evidence analysis, suspect identification, and case resolution rates. Through a mixed-methods approach involving interviews with law enforcement officers and analysis of 500 criminal cases, the study demonstrates that ML applications can improve investigation efficiency by up to 34%.',
                'status'         => 'approved',
                'user_index'     => 0,
            ],
            [
                'title'          => 'Sustainable Infrastructure Design for Coastal Communities in Batangas',
                'submission_category' => 'research',
                'type'           => 'Applied Research',
                'author_name'    => 'Prof. Maria Santos',
                'department'     => 'College of Engineering and Architecture',
                'year_published' => 2024,
                'keywords'       => 'sustainable design, coastal engineering, infrastructure, climate change',
                'abstract'       => 'This paper presents a comprehensive study of sustainable infrastructure design principles applicable to coastal communities in Batangas Province. Considering the increasing threats from sea-level rise and extreme weather events, the research proposes adaptive engineering solutions that integrate traditional knowledge with modern construction techniques. Case studies from three coastal barangays demonstrate cost-effective approaches to flood-resilient housing and community infrastructure.',
                'status'         => 'approved',
                'user_index'     => 1,
            ],
            [
                'title'          => 'E-Commerce Adoption Among SMEs in the Post-Pandemic Philippines',
                'submission_category' => 'journal',
                'type'           => 'Journal Article',
                'author_name'    => 'Dr. Ahmed Hassan',
                'department'     => 'College of Business and Management',
                'year_published' => 2023,
                'keywords'       => 'e-commerce, SME, digital transformation, pandemic, Philippines',
                'abstract'       => 'The COVID-19 pandemic accelerated digital transformation across Philippine businesses. This journal article examines the adoption patterns, barriers, and success factors of e-commerce implementation among small and medium enterprises (SMEs). Drawing from survey data of 350 SME owners across Luzon, Visayas, and Mindanao, findings reveal that businesses that pivoted to digital platforms experienced 28% higher survival rates during lockdown periods.',
                'status'         => 'approved',
                'user_index'     => 2,
            ],
            [
                'title'          => 'Recidivism Prevention Through Community-Based Rehabilitation Programs',
                'submission_category' => 'research',
                'type'           => 'Qualitative Research',
                'author_name'    => 'Prof. Ana Reyes',
                'department'     => 'College of Criminal Justice Education',
                'year_published' => 2023,
                'keywords'       => 'recidivism, rehabilitation, community programs, criminal justice reform',
                'abstract'       => 'This dissertation evaluates the effectiveness of community-based rehabilitation programs in reducing recidivism rates among former offenders in Metro Manila. The longitudinal study tracked 200 program participants over three years, comparing outcomes with a control group of 200 non-participants. Results indicate that community-integrated rehabilitation programs reduced re-offending rates by 42%, with the most significant impacts observed in vocational training and family reintegration components.',
                'status'         => 'approved',
                'user_index'     => 3,
            ],
            [
                'title'          => 'Advanced Navigation Systems for Philippine Archipelagic Waters',
                'submission_category' => 'research',
                'type'           => 'Applied Research',
                'author_name'    => 'Dr. Carlo Bautista',
                'department'     => 'College of Maritime Studies',
                'year_published' => 2024,
                'keywords'       => 'marine navigation, archipelago, GPS, safety, maritime',
                'abstract'       => 'The Philippines, as an archipelagic nation with over 7,600 islands, faces unique challenges in maritime navigation safety. This research develops and evaluates an enhanced navigation system specifically designed for Philippine inter-island waters, incorporating real-time weather data, shallow water mapping, and AI-based collision avoidance. Field tests conducted with 15 vessels across different Philippine sea routes demonstrated a 67% improvement in navigational safety metrics.',
                'status'         => 'approved',
                'user_index'     => 4,
            ],
            [
                'title'          => 'Blockchain Technology in Philippine Healthcare Record Management',
                'submission_category' => 'journal',
                'type'           => 'Journal Article',
                'author_name'    => 'Dr. Juan dela Cruz',
                'department'     => 'College of Computer Studies',
                'year_published' => 2024,
                'keywords'       => 'blockchain, healthcare, EHR, data security, Philippines',
                'abstract'       => 'This conference paper presents a prototype blockchain-based electronic health record (EHR) system tailored for Philippine healthcare institutions. The proposed system addresses critical issues of data privacy, interoperability between health facilities, and patient consent management. A pilot implementation at two regional hospitals in Central Luzon demonstrated improved record accuracy, reduced administrative overhead by 31%, and enhanced patient data security.',
                'status'         => 'approved',
                'user_index'     => 0,
            ],
            [
                'title'          => 'Impact of Ecotourism on Indigenous Community Livelihoods in Palawan',
                'submission_category' => 'research',
                'type'           => 'Thesis',
                'author_name'    => 'Reina Magno',
                'department'     => 'College of Tourism and Hospitality Management',
                'year_published' => 2023,
                'keywords'       => 'ecotourism, indigenous, Palawan, livelihood, sustainability',
                'abstract'       => 'This thesis investigates the socioeconomic impact of ecotourism initiatives on indigenous Batak and Tagbanua communities in Palawan. Through ethnographic fieldwork and economic analysis spanning 18 months, the study reveals both opportunities and tensions created by tourism development. While ecotourism provides supplementary income, the research identifies risks of cultural commodification and recommends community-led governance models for sustainable tourism development.',
                'status'         => 'pending',
                'user_index'     => 0,
            ],
        ];

        foreach ($samples as $s) {
            Research::create([
                'title'          => $s['title'],
                'submission_category' => $s['submission_category'],
                'type'           => $s['type'],
                'author_name'    => $s['author_name'],
                'user_id'        => $createdResearchers[$s['user_index']]->id,
                'department'     => $s['department'],
                'year_published' => $s['year_published'],
                'keywords'       => $s['keywords'],
                'abstract'       => $s['abstract'],
                'status'         => $s['status'],
                'approved_by'    => $s['status'] === 'approved' ? $admin->id : null,
                'approved_at'    => $s['status'] === 'approved' ? now() : null,
                'view_count'     => rand(10, 400),
            ]);
        }
    }
}
