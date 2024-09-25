<?php

use Magento\Framework\App\Bootstrap;
use Magento\Framework\Encryption\Encryptor;
use Magento\Indexer\Model\Indexer\State;

include '../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);


/** @var \Magento\Indexer\Model\ResourceModel\Indexer\State\CollectionFactory $statesFactory */
$statesFactory = $objectManager->get(\Magento\Indexer\Model\ResourceModel\Indexer\State\CollectionFactory::class);


/** @var \Magento\Indexer\Model\Indexer\StateFactory $stateFactory */
$stateFactory = $objectManager->get(\Magento\Indexer\Model\Indexer\StateFactory::class);


/** @var \Magento\Framework\Indexer\ConfigInterface $config */
$config = $objectManager->get(\Magento\Framework\Indexer\ConfigInterface::class);


/** @var \Magento\Framework\Encryption\EncryptorInterface $encryptor */
$encryptor = $objectManager->get(\Magento\Framework\Encryption\EncryptorInterface::class);


/** @var \Magento\Framework\Json\EncoderInterface $encoder */
$encoder = $objectManager->get(\Magento\Framework\Json\EncoderInterface::class);



/** @var State[] $stateIndexers */
$stateIndexers = [];
$states = $statesFactory->create();
foreach ($states->getItems() as $state) {
    /** @var State $state */
    $stateIndexers[$state->getIndexerId()] = $state;
}

foreach ($config->getIndexers() as $indexerId => $indexerConfig) {
    $expectedHashConfig = $encryptor->hash(
        $encoder->encode($indexerConfig),
        Encryptor::HASH_VERSION_MD5
    );
//    echo $expectedHashConfig. "    ======  ".$stateIndexers[$indexerId]->getHashConfig().PHP_EOL;
//    print_r($indexerConfig);
//    echo PHP_EOL;

    if (isset($stateIndexers[$indexerId])) {
        if ($stateIndexers[$indexerId]->getHashConfig() != $expectedHashConfig) {
            //$stateIndexers[$indexerId]->setStatus(StateInterface::STATUS_INVALID);
            $stateIndexers[$indexerId]->setHashConfig($expectedHashConfig);
            $stateIndexers[$indexerId]->save();
        }
    } else {
        /** @var State $state */
        $state = $stateFactory->create();
        $state->loadByIndexer($indexerId);
        $state->setHashConfig($expectedHashConfig);
//        $state->setStatus(StateInterface::STATUS_INVALID);
        $state->save();
    }
}

echo 'Done.' . PHP_EOL;
