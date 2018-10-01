<?php

namespace FP\DoctrineEncryptBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Doctrine\Common\Persistence\ObjectManager;
use \Exception;
use \ReflectionProperty;
use FP\DoctrineEncryptBundle\Subscribers\AbstractDoctrineEncryptSubscriber as ADES;
use FP\DoctrineEncryptBundle\Configuration\Encrypted;
use Doctrine\Common\Persistence\ObjectRepository;
use FP\DoctrineEncryptBundle\Interfaces\StandardizerInterface;

/**
 * Description of UpdateDataCommand
 *
 * @author wpigott
 */
class UpdateDataCommand extends ContainerAwareCommand {

    private $objectManager;

	private $standardizer;

    protected function configure() {
        $this
                ->setName('doctrine:encrypt:update')
                ->setDescription('Update all data registered with Doctrine.  Encrypt and Decrypt all values as required by the annotations.')
                ->addOption('mem-limit', null, InputArgument::OPTIONAL, 'Allows the memory limit to be overridden by passing in the number of MB.');
    }

    /**
     * 
     * @return ObjectManager
     * @throws Exception
     */
    protected function getObjectManager() {
        if (!$this->objectManager) {
            $this->objectManager = $this->getStandardizer()->getObjectManager();
        }
        return $this->objectManager;
    }

    /**
     * 
     * @return StandardizerInterface
     */
    protected function getStandardizer() {
		return $this->standardizer;
    }

    protected function getAnnotationReader() {
        return $this->getContainer()->get('annotation_reader');
    }

    protected function getClassList() {
        $classes = array();

        // Check each class metadata
        foreach ($this->getObjectManager()->getMetadataFactory()->getAllMetadata() as $metadata) {

            // Get the reflection class
            $reflectionClass = $metadata->getReflectionClass();

            // Ignore abstract classes
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            // Check each property for the Encrypted annotation
            foreach ($reflectionClass->getProperties() as $refProperty) {
                $annotation = $this->getAnnotation($refProperty);
                if ($annotation instanceof Encrypted) {
                    $classes[$metadata->getName()] = TRUE;
                    break;
                }
            }
        }
        return array_keys($classes);
    }

    /**
     * 
     * @param ReflectionProperty $reflectionProperty
     * @return Encrypted|NULL
     */
    protected function getAnnotation(ReflectionProperty $reflectionProperty) {
        return $this->getAnnotationReader()->getPropertyAnnotation($reflectionProperty, ADES::ENCRYPTED_ANN_NAME);
    }

    protected function processRepository(ObjectRepository $repository, InputInterface $input, OutputInterface $output) {
        $standardizer = $this->getStandardizer();

        $objects = $repository->findAll();
        $output->writeln('Total Objects: ' . count($objects));
        $output->write('Processed: ');
        $count = 0;
        foreach ($objects as $object) {
            // Mark it as updated
            $standardizer->scheduleObjectForUpdate($object);
            $count++;
            if (($count % 50) == 0) {
                $this->flushAndClear();
                $output->write($count . ' | ');
            }
        }
        $this->flushAndClear();
        $output->writeln($count);
    }

    protected function flushAndClear() {
        $this->getObjectManager()->flush();
        $this->getObjectManager()->clear();
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        // Set the memory limit if one is provided.
        if (($input->hasOption('mem-limit')) && (NULL !== $input->getOption('mem-limit'))) {
            $limit = $input->getOption('mem-limit');
            if ((!ctype_digit($limit)) || (16384 < (int) $limit)) {
                $output->writeln('Memory limit must be a positive integer less than or equal to 16384.');
                return;
            }
            ini_set('memory_limit', $limit . 'M');
            $output->writeln('<info>Processing with elevated memory limit of '.$limit.'M.</info>');
        }

		$db_drivers = $this->getContainer()->getParameter('fp_doctrine_encrypt.db_driver');

		foreach($db_drivers as $db_driver){
			$output->writeln('<info>Work on Driver '.$db_driver.'.</info>');
			$this->objectManager = null;
			$this->standardizer = $this->getContainer()->get('fp_doctrine_encrypt.object_manager.'.$db_driver);

			// Get the list of all collections
			$output->writeln('<info>Loading list of classes.</info>');
			foreach($this->getClassList() as $className){
				// Make sure we have a repository for each class.
				try{
					$repository = $this->getObjectManager()->getRepository($className);
				} catch(MappingException $ex){
					continue;
				}

				$output->writeln('<info>Processing encryption for: "' . $className . '".</info>');

				// Now process the repository
				$this->processRepository($repository, $input, $output);
			}
		}

        $output->writeln('<info>Encryption update complete.</info>');
    }

}
