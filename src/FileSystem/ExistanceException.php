<?php
	namespace STEIN197\FileSystem;

	class ExistanceException extends DescriptorException {

		public function getDefaultMessage(): string {
			if ($this->desc instanceof Directory)
				$prefix = 'Directory';
			elseif ($this->desc instanceof File)
				$prefix = 'File';
			elseif ($this->desc instanceof SymLink)
				$prefix = 'Symbolic link';
			else
				$prefix = 'Descriptor';
			return "{$prefix} '{$this->desc}' does not exist";
		}
	}
