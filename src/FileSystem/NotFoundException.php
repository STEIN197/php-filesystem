<?php
	namespace STEIN197\FileSystem;

	class NotFoundException extends DescriptorException {
		
		protected function getDefaultMessage(): string {
			$substisution = null;
			switch (true) {
				case $this->desc instanceof Directory:
					$substisution = 'Directory';
					break;
				case $this->desc instanceof File:
					$substisution = 'File';
					break;
				case $this->desc instanceof SymLink:
					$substisution = 'Symbolic link';
					break;
				default:
					$substisution = 'Descriptor';
			}
			return "{$substisution} '{$this->desc}' does not exist";
		}
	}
