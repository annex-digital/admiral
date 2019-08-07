<?php
declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Archetype;
use App\Entity\Project;
use App\Message\SetProjectVariables;
use App\PlatformClient;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Platformsh\Client\Model\Project as PshProject;
use Symfony\Component\Messenger\MessageBusInterface;

class DoctrineArchetype
{
    /**
     * @var PlatformClient
     */
    protected $client;

    /**
     * @var MessageBusInterface
     */
    protected $messageBus;

    public function __construct(PlatformClient $client, MessageBusInterface $messageBus)
    {
        $this->client = $client;
        $this->messageBus = $messageBus;
    }

    /**
     * Acts on an object just after it has been updated.
     *
     * @param Archetype $archetype
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(Archetype $archetype, LifecycleEventArgs $args)
    {
        // This is a potentially large number of projects, so throw them onto the
        // message bus. That way they can easily be made asynchronous by changing
        // the bus transport.

        /** @var Project $project */
        foreach ($archetype->getProjects() as $project) {
            $this->messageBus->dispatch(new SetProjectVariables($archetype->getId(), $project->getProjectId()));
        }
    }

    /**
     * Returns the Platform.sh project object that corresponds to the provided local Project reference.
     *
     * @param Project $project
     * @return PshProject
     */
    protected function getPshProject(Project $project) : PshProject
    {
        return $this->client->getProject($project->getProjectId());
    }
}
