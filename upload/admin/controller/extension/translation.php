<?php
class ControllerExtensionTranslation extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/translation');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/translation', 'token=' . $this->session->data['token'], true)
		);

		$data['heading_title'] = $this->language->get('heading_title');

        $data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');
		$data['text_progress'] = $this->language->get('text_progress');
		$data['text_available'] = $this->language->get('text_available');
		$data['text_crowdin'] = $this->language->get('text_crowdin');
		
		$data['column_flag'] = $this->language->get('column_flag');
		$data['column_name'] = $this->language->get('column_name');
		$data['column_progress'] = $this->language->get('column_progress');
        $data['column_action'] = $this->language->get('column_action');

		$data['entry_progress'] = $this->language->get('entry_progress');

		$data['button_install'] = $this->language->get('button_install');
		$data['button_uninstall'] = $this->language->get('button_uninstall');

		$data['token'] = $this->session->data['token'];

		$data['error_warning'] = '';

		// Try to get a cached translations array
		$translations = $this->cache->get('translation');
		
		if (!$translations) {
			$curl = curl_init('https://api.crowdin.com/api/project/opencart/status?key=a00e7b58c0790df4126273119b318db5');
	
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, 'json=true');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	
			$response = curl_exec($curl);
	
			if (!$response) {
				$data['error_warning'] = sprintf($this->language->get('error_api'), curl_error($curl), curl_errno($curl));
			} else {
				$translations = json_decode($response, true);
				
				$this->cache->set('translation', $translations);				
			}
			
			curl_close($curl);
		}
		
		$data['translations'] = array();
	
		$translation_total = count($translations);
		
		if ($translations) {
			$translations = array_splice($translations, ($page - 1) * 16, 16);
			
			foreach ($translations as $translation){
				if (is_dir(DIR_LANGUAGE . strtolower($translation['code']))) {
					$installed = true;
				} else {
					$installed = false;
				}
				
				$data['translations'][] = array(
					'name'      => $translation['name'],
					'code'      => $translation['code'],
					'image'     => 'https://d1ztvzf22lmr1j.cloudfront.net/images/flags/' . $translation['code'] . '.png',
					'progress'  => $translation['translated_progress'],
					'install'   => $this->url->link('extension/translation/install', 'token=' . $this->session->data['token'] . '&code=' . $translation['code'], true),
					'uninstall' => $this->url->link('extension/translation/uninstall', 'token=' . $this->session->data['token'] . '&code=' . $translation['code'], true),
					'installed' => $installed
				);
			}
		}

		$pagination = new Pagination();
		$pagination->total = $translation_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/translation', 'token=' . $this->session->data['token'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($translation_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($translation_total - $this->config->get('config_limit_admin'))) ? $translation_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $translation_total, ceil($translation_total / $this->config->get('config_limit_admin')));

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/translation', $data));
	}

	public function install() {
		$this->load->language('extension/translation');
		
		$json = array();

		// Check user has permission
		if (!$this->user->hasPermission('modify', 'extension/translation')) {
			$json['error'] = $this->language->get('error_permission');
		}
		
		if (isset($this->request->get['code'])) {
			$code = $this->request->get['code'];
		} else {
			$code = '';
		}
				
		if (!$json) {
			$json['step'] = array();
			
			// Download
			$json['step'][] = array(
				'text' => $this->language->get('text_download'),
				'href' => str_replace('&amp;', '&', $this->url->link('extension/translation/download', 'token=' . $this->session->data['token'] . '&code=' . $code, true))
			);			
			
			// Zip
			$json['step'][] = array(
				'text' => $this->language->get('text_unzip'),
				'href' => str_replace('&amp;', '&', $this->url->link('extension/translation/unzip', 'token=' . $this->session->data['token'] . '&code=' . $code, true))
			);
			
			/*
			// FTP
			$json['step'][] = array(
				'text' => $this->language->get('text_ftp'),
				'href' => str_replace('&amp;', '&', $this->url->link('extension/translation/ftp', 'token=' . $this->session->data['token'] . '&code=' . $code, true))
			);
			
			// Clear temporary files
			$json['step'][] = array(
				'text' => $this->language->get('text_remove'),
				'href' => str_replace('&amp;', '&', $this->url->link('extension/translation/remove', 'token=' . $this->session->data['token'] . '&code=' . $code, true))
			);
			*/						
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function download() {
		$this->load->language('extension/translation');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}
		
		if (isset($this->request->get['code'])) {
			$code = $this->request->get['code'];
		} else {
			$code = '';
		}
				
		if (!$json) {	
			$curl = curl_init('https://api.crowdin.com/api/project/opencart/download/' . $code . '.zip?key=a00e7b58c0790df4126273119b318db5');
	
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, 'json=true');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	
			$response = curl_exec($curl);
	
			if (!$response) {
				$json['error'] = sprintf($this->language->get('error_api'), curl_error($curl), curl_errno($curl));
			} else {
				$file = DIR_UPLOAD  . $code . '.zip';
		
				$handle = fopen($file, 'w');
		
				flock($handle, LOCK_EX);
		
				fwrite($handle, $response);
		
				fflush($handle);
		
				flock($handle, LOCK_UN);
		
				fclose($handle);			
			}
			
			curl_close($curl);	
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));				
	}
	
	public function unzip() {
		$this->load->language('extension/translation');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/translation')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (isset($this->request->get['code'])) {
			$code = $this->request->get['code'];
		} else {
			$code = '';
		}

		$file = DIR_UPLOAD  . $code . '.zip';
		
		if (!is_file($file) || substr(str_replace('\\', '/', realpath($file)), 0, strlen(DIR_UPLOAD)) != DIR_UPLOAD) {
			$json['error'] = $this->language->get('error_file');
		}
			
		if (!$json) {	
			// Unzip the files
			$zip = new ZipArchive();

			if ($zip->open($file)) {
				$zip->extractTo(DIR_UPLOAD . $code . '/');
				$zip->close();
			} else {
				$json['error'] = $this->language->get('error_unzip');
			}

			// Remove Zip
			unlink($file);
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}

	public function ftp() {
		$this->load->language('extension/installer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/translation')) {
			$json['error'] = $this->language->get('error_permission');
		}

		// Check FTP status
		if (!$this->config->get('config_ftp_status')) {
			$json['error'] = $this->language->get('error_ftp_status');
		}
		
		if (isset($this->request->get['code'])) {
			$code = $this->request->get['code'];
		} else {
			$code = '';
		}
		
		$directory = DIR_UPLOAD . $code . '/2.0.0.x/';

		if (!is_dir($directory) || substr(str_replace('\\', '/', realpath($directory)), 0, strlen(DIR_UPLOAD)) != DIR_UPLOAD) {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!$json) {
			// Get a list of files ready to upload
			$files = array();

			$path = array($directory . '*');

			while (count($path) != 0) {
				$next = array_shift($path);

				foreach ((array)glob($next) as $file) {
					if (is_dir($file)) {
						$path[] = $file . '/*';
					}

					$files[] = $file;
				}
			}

			$language_path = "language/" . $this->request->get['code'];

			if (!is_dir(DIR_APPLICATION . $language_path)) {
				mkdir(DIR_APPLICATION . $language_path, 0755);
			}

			if (!is_dir(DIR_CATALOG . $language_path)) {
				mkdir(DIR_CATALOG . $language_path, 0755);
			}

			// Connect to the site via FTP
			$connection = ftp_connect($this->config->get('config_ftp_hostname'), $this->config->get('config_ftp_port'));

			if ($connection) {
				$login = ftp_login($connection, $this->config->get('config_ftp_username'), $this->config->get('config_ftp_password'));

				if ($login) {
					if ($this->config->get('config_ftp_root')) {
						$root = ftp_chdir($connection, $this->config->get('config_ftp_root'));
					} else {
						$root = ftp_chdir($connection, '/');
					}

					if ($root) {
						foreach ($files as $file) {
							$destination = substr($file, strlen($directory));

							// Upload everything in the upload directory
							// Many people rename their admin folder for security purposes which I believe should be an option during installation just like setting the db prefix.
							// the following code would allow you to change the name of the following directories and any extensions installed will still go to the right directory.
							if (substr($destination, 0, 5) == 'admin') {
								$destination = basename(DIR_APPLICATION). "/" . $language_path . substr($destination, 5);
							}

							if (substr($destination, 0, 7) == 'catalog') {
								$destination = basename(DIR_CATALOG). "/" . $language_path . substr($destination, 7);
							}

							if (substr($destination, 0, 5) == 'image') {
								$destination = basename(DIR_IMAGE) . substr($destination, 5);
							}

							if (substr($destination, 0, 6) == 'system') {
								$destination = basename(DIR_SYSTEM) . substr($destination, 6);
							}

							if (is_dir($file)) {
								$lists = ftp_nlist($connection, substr($destination, 0, strrpos($destination, '/')));

								// Basename all the directories because on some servers they don't return the fulll paths.
								$list_data = array();

								foreach ($lists as $list) {
									$list_data[] = basename($list);
								}

								if (!in_array(basename($destination), $list_data)) {
									if (!ftp_mkdir($connection, $destination)) {
										$json['error'] = sprintf($this->language->get('error_ftp_directory'), $destination);
									}
								}
							}

							if (is_file($file)) {
								if (!ftp_put($connection, $destination, $file, FTP_BINARY)) {
									$json['error'] = sprintf($this->language->get('error_ftp_file'), $file);
								}
							}
						}
					} else {
						$json['error'] = sprintf($this->language->get('error_ftp_root'), $root);
					}
				} else {
					$json['error'] = sprintf($this->language->get('error_ftp_login'), $this->config->get('config_ftp_username'));
				}

				ftp_close($connection);
			} else {
				$json['error'] = sprintf($this->language->get('error_ftp_connection'), $this->config->get('config_ftp_hostname'), $this->config->get('config_ftp_port'));
			}
		}

		echo "download success";

		$this->remove();
	}

	public function remove() {
		$this->load->language('extension/installer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/translation')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$directory = DIR_UPLOAD . '/2.0.0.x';

		if (!is_dir($directory) || substr(str_replace('\\', '/', realpath($directory)), 0, strlen(DIR_UPLOAD)) != DIR_UPLOAD) {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!$json) {
			// Get a list of files ready to upload
			$files = array();

			$path = array($directory);

			while (count($path) != 0) {
				$next = array_shift($path);

				// We have to use scandir function because glob will not pick up dot files.
				foreach (array_diff(scandir($next), array('.', '..')) as $file) {
					$file = $next . '/' . $file;

					if (is_dir($file)) {
						$path[] = $file;
					}

					$files[] = $file;
				}
			}

			rsort($files);

			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);

				} elseif (is_dir($file)) {
					rmdir($file);
				}
			}

			if (file_exists($directory)) {
				rmdir($directory);
			}
			
			$this->response->redirect($this->url->link(!empty($data['redirect']) ? $data['redirect'] : 'extension/translation', 'token=' . $this->session->data['token'], true));
			
			
			$json['success'] = $this->language->get('text_success');
		}
	}
}