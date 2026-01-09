<?php

namespace App\Command;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-demo-data',
    description: 'Initialize demo data for the agenda',
)]
class InitDemoDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des données de démonstration');

        // Vérifier si des événements existent déjà
        $existingEvents = $this->entityManager->getRepository(Event::class)->findAll();
        if (count($existingEvents) > 0) {
            $io->warning('Des événements existent déjà dans la base de données.');
            if (!$io->confirm('Voulez-vous continuer et ajouter les événements de démonstration ?', false)) {
                return Command::SUCCESS;
            }
        }

        // Créer les événements de démonstration
        $demoEvents = [
            [
                'title' => 'Cours de Mathématiques',
                'start' => '2026-01-07 09:00:00',
                'end' => '2026-01-07 11:00:00',
                'type' => 'course',
                'location' => 'Salle B204',
                'description' => 'Cours de mathématiques pour BTS SIO 1ère année',
                'color' => '#3788d8'
            ],
            [
                'title' => 'Réunion Pédagogique',
                'start' => '2026-01-07 14:00:00',
                'end' => '2026-01-07 16:00:00',
                'type' => 'meeting',
                'location' => 'Salle des professeurs',
                'description' => 'Bilan du trimestre et axes d\'amélioration',
                'color' => '#4caf50'
            ],
            [
                'title' => 'Examen BTS SIO',
                'start' => '2026-01-08 10:00:00',
                'end' => '2026-01-08 12:00:00',
                'type' => 'exam',
                'location' => 'Salle A101',
                'description' => 'Examen de développement web',
                'color' => '#f44336'
            ],
            [
                'title' => 'Formation Continue',
                'start' => '2026-01-09 09:00:00',
                'end' => '2026-01-09 17:00:00',
                'type' => 'training',
                'location' => 'Salle C301',
                'description' => 'Formation sur les nouvelles technologies',
                'color' => '#ff9800'
            ]
        ];

        foreach ($demoEvents as $eventData) {
            $event = new Event();
            $event->setTitle($eventData['title']);
            $event->setStartDate(new \DateTime($eventData['start']));
            $event->setEndDate(new \DateTime($eventData['end']));
            $event->setType($eventData['type']);
            $event->setLocation($eventData['location']);
            $event->setDescription($eventData['description']);
            $event->setColor($eventData['color']);

            $this->entityManager->persist($event);
            $io->success('Événement créé : ' . $eventData['title']);
        }

        $this->entityManager->flush();

        $io->success('Toutes les données de démonstration ont été créées avec succès !');

        return Command::SUCCESS;
    }
}
