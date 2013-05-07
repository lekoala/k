<?php

namespace k\fs;

use \FilterIterator;

/**
 * Description of ImageFilterIterator
 *
 * @author Koala
 */
class ImageFilterIterator extends FilterIterator {
	
	protected $extensions = array('jpg','jpeg','png','gif');
	
    public function accept()
    {
        if(in_array(strtolower($this->current()->getExtension()),$this->extensions)) {
			return true;
		}
    }
}
