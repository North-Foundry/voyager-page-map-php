<?php

declare(strict_types=1);

use NorthFoundry\VoyagerPageMap\Configuration\VPMConfiguration;
use NorthFoundry\VoyagerPageMap\Contract\VPMElementInterface;
use NorthFoundry\VoyagerPageMap\Contract\VPMTextSerializableInterface;
use NorthFoundry\VoyagerPageMap\Document\VPMDocument;
use NorthFoundry\VoyagerPageMap\Document\VPMDocumentMetadata;
use NorthFoundry\VoyagerPageMap\Element\AbstractVPMElement;
use NorthFoundry\VoyagerPageMap\VPM;

class_exists(VPMConfiguration::class);
interface_exists(VPMElementInterface::class);
interface_exists(VPMTextSerializableInterface::class);
class_exists(VPMDocument::class);
class_exists(VPMDocumentMetadata::class);
class_exists(AbstractVPMElement::class);
class_exists(VPM::class);
