<?php
	namespace STEIN197\FileSystem;

	/**
	 * This class is used to represent common exception that
	 * may happen during operations to descriptors such as
	 * files and directories.
	 */
	class DescriptorException extends \Exception {
		
		/** @var Descriptor Holds reference to the troubled descriptor. */
		protected Descriptor $desc;

		/**
		 * @param Descriptor $desc Descriptor that caused problem.
		 */
		public function __construct(Descriptor $desc, ?string $message = null, ?int $code = 0) {
			$this->desc = $desc;
			$this->message = $message ?? $this->getDefaultMessage();
			$this->code = $code;
		}

		/**
		 * Return the descriptor that caused exception.
		 * @return Descriptor Troubled descriptor.
		 */
		public final function getDescriptor(): Descriptor {
			return $this->desc;
		}

		/**
		 * Create default message if {@see DescriptorException::$message} is null.
		 * @return string Default message.
		 */
		protected function getDefaultMessage(): string {
			return "Cannot do operation to '{$this->desc}' descriptor";
		}
	}
