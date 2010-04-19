<?php

abstract class MM_Mail_Core extends Model{
	
	public $tpl_data;
	public $recipient;
	public $template;
	public $subject;
	public $replyto;
	public $text_body;
	public $html_body;
	public $attachment;
	private $swift;
	private $config;
	
	public function __construct($template = NULL, $tpl_data = NULL){
		parent::__construct();
				
		$this->config = Kohana::config('email');
		
		if($template !== NULL){
			$this->template = $template;
		}
		
		if($tpl_data !== NULL){
			$this->tpl_data = $tpl_data;
		}
		
		return $this;
	}
	
	public static function template($template = NULL, $tpl_data = NULL){
		
		$obj = new MM_Mail($template, $tpl_data);
		
		return $obj;
	}
	
	public function connect(){
		
		try{
			if (!class_exists('Swift_Mailer', FALSE)){
				require Kohana::find_file('vendor', 'swift/lib/classes/Swift');
			}
		
			if(!in_array('Swift_ClassLoader', spl_autoload_functions())){
				Swift::registerAutoload();	
			}
		
			require_once Kohana::find_file('vendor', 'swift/lib/swift_init');
		
			$port = $this->config['options']['port'];
				
			$transport = Swift_SmtpTransport::newInstance($this->config['options']['hostname'], $port);
			$transport->setTimeout($this->config['options']['timeout']);
		
			$this->swift = Swift_Mailer::newInstance($transport);
		
			return true;
			
		}catch(Swift_Exception $e){
			echo $e;
			return false;
		}
	}
	
	public function disconnect(){

		spl_autoload_unregister(array('Swift_ClassLoader', 'load'));
		return true;		
	}

		
	public function get_template(){
		
		if(isset($this->template) AND !empty($this->template)){
			
			if(is_string($this->template)){
			
				if(isset($this->tpl_data) AND !empty($this->tpl_data)){
					
					if(is_array($this->tpl_data) OR is_object($this->tpl_data)){
						$tpl_data = $this->tpl_data;
					}else{
						throw new Kohana_Exception('Invalid type for tpl_data');
					}
				}else{
					$tpl_data = array();
				}
			
				$template_class = 'MM_Mail_Tpl_'.Text::ucwords($this->template, '_');
				
				if(class_exists($template_class)){
					
					$this->template = new $template_class($tpl_data);
					
					$recipient = $this->template->get_recipient();
					
					// echo Kohana::debug($recipient);
					
					if(!empty($recipient) AND (!isset($this->recipient) OR empty($this->recipient))){
					
						$this->recipient = $recipient;
					}
					
					// echo Kohana::debug($this->recipient);
					
					
					$sender = $this->template->get_sender();
					if(!empty($sender) AND (!isset($this->sender) OR empty($this->sender))){
						$this->sender = $sender;
					}
					
					$subject = $this->template->get_subject();
					if(!empty($subject) AND (!isset($this->subject) OR empty($this->subject))){
						$this->subject = $subject;
					}
					
					$text_body = $this->template->get_text();
					if(!empty($text_body) AND (!isset($this->text_body) OR empty($this->text_body))){
						$this->text_body = $text_body;
					}
					
					$html_body = $this->template->get_html();
					if(!empty($html_body) AND (!isset($this->html_body) OR empty($this->html_body))){
						$this->html_body = $html_body;
					}
					
					$attachment = $this->template->get_attachment();
					if(!empty($attachment) AND (!isset($this->attachment) OR empty($this->attachment))){
						$this->attachment = $attachment;
					}
					
					return true;
					
				}else{
					throw new Kohana_Exception('Class '.$template_class.' does not exist');
				}
			
			}else{
				throw new Kohana_Exception('Invalid type for template property');
			}	
		}
	}
	
	public function send(){
		
		$this->connect();
		
		$this->get_template();
		
		$message = Swift_Message::newInstance();
		$message->setCharset('utf-8');
				
		if(isset($this->recipient) AND !empty($this->recipient)){
			if (is_string($this->recipient)){
				
				if(Validate::email($this->recipient)){
					// Single recipient
					$message->setTo($this->recipient);
				}else{
					throw new Kohana_Exception('Invalid e-mail address for recipient');
				}
			}elseif (is_array($this->recipient)){
				if (isset($this->recipient[0]) AND isset($this->recipient[1])){
					
					if(Validate::email($this->recipient[1])){
						// Create To: address set
						$this->recipient = array('to' => $this->recipient);
					}else{
						throw new Kohana_Exception('Invalid e-mail address for recipient');
					}
				}

				foreach ($this->recipient as $method => $set){
					if ( ! in_array($method, array('to', 'cc', 'bcc'))){
						// Use To: by default
						$method = 'to';
					}

					// Create method name
					$method = 'add'.ucfirst($method);

					if (is_array($set)){
						
						if(Validate::email($set[1])){
						
							// Add a recipient with name
							$message->$method($set[0], $set[1]);
						}else{
							throw new Kohana_Exception('Invalid e-mail address for recipient');
						}
						
					}else{
						
						if(Validate::email($set)){
							// Add a recipient without name
							$message->$method($set);
						}else{
							throw new Kohana_Exception('Invalid e-mail address for recipient');
						}
					}
				}
			}else{
				throw new Kohana_Exception('Invalid recipient type');
			}
		}else{
			throw new Kohana_Exception('Recipient is required');
		}
		
		if(isset($this->sender) AND !empty($this->sender)){
			if (is_string($this->sender)){

				if(Validate::email($this->sender)){
					
					$message->setFrom($this->sender);
					$message->setSender($this->sender);
				}else{
					throw new Kohana_Exception('Invalid e-mail address for sender');
				}
			}elseif (is_array($this->sender)){
				
				$senders = array();
				foreach($this->sender as $email_address => $name){
					
					if(Validate::email($email_address)){
						$senders[$email] = $name;
						
						if(!isset($primary_sender)){
							$primary_sender = $email;
						}
					}else{
						throw new Kohana_Exception('Invalid e-mail address for sender');	
					}
					
				}
				
				if(count($senders) > 0 && isset($primary_sender)){
					$message->setFrom($senders);
					$message->setSender($primary_sender);
				}else{
					$message->setFrom(array($this->config['default']['sender'] => $this->config['default']['sender_name']));
					$message->setSender($this->config['default']['sender']);
				}
			}else{
				throw new Kohana_Exception('Invalid sender type');
			}
		}else{
			$message->setFrom(array($this->config['default']['sender'] => $this->config['default']['sender_name']));
			$message->setSender($this->config['default']['sender']);
		}

		if(isset($this->sender) AND !empty($this->sender)){
			if (is_string($this->sender)){
				
				if(Validate::email($this->sender)){

					$message->setFrom($this->sender);
				}else{
					throw new Kohana_Exception('Invalid e-mail address for sender');
				}
			}elseif (is_array($this->sender))
			{
				if(Validate::email($this->sender[1])){

					$message->setFrom($this->sender[0], $this->sender[1]);
				}else{
					throw new Kohana_Exception('Invalid e-mail address for sender');
				}
					
			}else{
				throw new Kohana_Exception('Invalid sender type');
			}
		}else{
			$message->setFrom($this->config['default']['sender']);
		}
		
		if(isset($this->subject) AND !empty($this->subject)){
			
			if(is_string($this->subject)){
				$message->setSubject($this->subject);
			}else{
				throw new Kohana_Exception('Invalid type for subject');
			}
			
		}else{
			$message->setSubject = '';
		}
		
		if((isset($this->text_body) AND !empty($this->text_body)) OR (isset($this->html_body) AND !empty($this->html_body))){
			
			if(isset($this->text_body) AND !empty($this->text_body)){
				
				if(is_string($this->text_body)){
					$message->addPart($this->text_body, 'text/plain');
				}else{
					throw new Kohana_Exception('Invalidy type for text_body');
				}
			}
			
			if(isset($this->html_body) AND !empty($this->html_body)){
				
				if(is_string($this->html_body)){
					$message->addPart($this->html_body, 'text/html');
				}else{
					throw new Kohana_Exception('Invalidy type for html_body');
				}
			}
			
		}else{
			throw new Kohana_Exception('Either text_body or html_body must be set');
		}	
		
		if(isset($this->attachment) AND !empty($this->attachment)){
			
			if(is_string($this->attachment)){
				
				if(file_exists($this->attachment)){
					$message->attach(Swift_Attachment::fromPath($this->attachment));
				}elseif(Validate::url($this->attachment)){
					$message->attach(Swift_Attachment::fromPath($this->attachment));
				}else{
					throw new Kohana_Exception('Could not find file attachment');
				}
				
			}elseif(is_array($this->attachment)){
				
				foreach($this->attachment as $file){
					
					if(is_string($file)){
						if(file_exists($file)){
							$message->attach(Swift_Attachment::fromPath($file));
						}elseif(Validate::url($file)){
							$message->attach(Swift_Attachment::fromPath($file));
						}else{
							throw new Kohana_Exception('Could not find file attachment');
						}
					}else{
						throw new Kohana_Exception('Invalid type for file attachment');
					}
				}
				
			}else{
				throw new Kohana_Exception('Invalid type for file attachment');
			}
			
		}
		
		if(isset($this->replyto) && !empty($this->replyto)){
			
			if(is_string($this->replyto)){
				
				if(Validate::email($this->replyto)){
					$message->setReplyTo($this->replyto);
				}else{
					throw new Kohana_Exception('Invalid email address for reply to');
				}
				
			}else{
				throw new Kohana_Exception('Invalid type for reply to');
			}
		}
		
		if($this->swift->send($message)){
			$this->disconnect();
			return true;
		}else{
			$this->disconnect();
			return false;
		}
	}
	
	
}