<?php
namespace ProjectSoft\Evolution;

class Video {

	/** Ссылка на ролик */
	private $link;

	/** Видеохостинг */
	private $hosting;

	/** данные видео */
	private $videoInfo = array();

	/** Автоматическое сохранение изображения */
	private $autosave = false;

	/** Ссылка на каталог перевьюшек */
	private $dir_images = "assets/images/video/";

	const YOUTUBE = 'youtube';
	const RUTUBE  = 'rutube';
	const DEF     = 'default';

	/**
	 * @param string|null $link ссылка на видео
	 */
	public function __construct(string $link = null, bool $autosave = false, array &$videoInfo = array())
	{
		$this->autosave = $autosave ? true : false;
		if (!empty($link)) {
			$videoInfo = $this->cleanLink($link)->getVideoInfo();
		}
	}


	/** Проверка и подготовка ссылки и частей */
	private function cleanLink($link)
	{
		if (!preg_match('/^(http|https)\:\/\//i', $link)) {
			$this->link = 'https://' . $link;
		}else{
			$this->link = preg_replace('/^(?:https?):\/\//i', 'https://', $link, 1);
		}
		return $this;
	}

	/** Определяем хостинг и получаем информацию о видео */
	private function getVideoInfo()
	{
		$re_youtube = '/^(?:https?\:\/\/(?:[w]{3}\.)?)(youtu(?:\.be|be\.com))\//i';
		$re_rutube  = '/^(?:https?\:\/\/(?:[w]{3}\.)?)(rutube\.ru)/i';
		if(preg_match($re_youtube, $this->link)){
			$this->hosting = self::YOUTUBE;
			return $this->getYouTubeInfo();
		}elseif(preg_match($re_rutube, $this->link)){
			$this->hosting = self::RUTUBE;
			return $this->getRuTubeInfo();
		}
		$this->hosting = self::DEF;
		return array();
	}

	/** Получение информации с RuTube */
	private function getRuTubeInfo()
	{
		$re = '/\/video\/([\w\-_]+)/i';
		preg_match($re, $this->link, $match);
		if(count($match)){
			$id = $match[1];
			$link = "https://rutube.ru/api/video/" . $id . "/?format=json";
			$str = $this->fetchPage($link);
			$json = json_decode($str, true);
			if($json['track_id']){
				$this->videoInfo['id'] = $json['track_id'];
				$this->videoInfo['video'] = '<div class="embed"><div class="embed-responsive embed-responsive-16by9"><iframe src="https://rutube.ru/play/embed/' . $json['track_id'] . '" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture" webkitAllowFullScreen mozallowfullscreen allowfullscreen></iframe></div></div>';
				/** Скачать и сохранить если сохраняется документ */
				@mkdir(MODX_BASE_PATH . $this->dir_images . $this->hosting . '/', 0755, true);
				$img_file = $this->dir_images . $this->hosting . '/' . $json['track_id'] . '.jpg';
				if(is_file($img_file)){
					$this->videoInfo['image'] = $img_file;
				}else{
					$this->videoInfo['image'] = $json['thumbnail_url'];
				}
				if($this->autosave){
					$img = $this->fetchPage($json['thumbnail_url']);
					@file_put_contents(MODX_BASE_PATH . $img_file, $img);
					if(is_file(MODX_BASE_PATH . $img_file)){
						$this->videoInfo['image'] = $img_file;
					}else{
						$this->videoInfo['image'] = $json['thumbnail_url'];
					}
				}
			}else{
				return array();
			}
		}else{
			return array();
		}
		return $this->videoInfo;
	}

	/** Получение информации с YouTube */
	private function getYouTubeInfo()
	{
		$re = '#(?<=(?:v|i)=)[a-z0-9-_]+(?=&)|(?<=(?:v|i)\/)[^&\n]+|(?<=embed\/)[^"&\n]+|(?<=(?:v|i)=)[^&\n]+|(?<=youtu.be\/)[^&\n]+#i';
		preg_match($re, $this->link, $match);
		if(count($match)){
			$this->videoInfo['id'] = $match[0];
			$embed = 'https://www.youtube.com/embed/' . $match[0] . '?';
			parse_str(parse_url($this->link, PHP_URL_QUERY), $params);
			if($params['list']){
				$embed .= 'list=' . $params['list'] . '&';
			}
			$embed .= 'showinfo=0&modestbranding=1&rel=0';
			$this->videoInfo['video'] = '<div class="embed"><div class="embed-responsive embed-responsive-16by9"><iframe src="' . $embed . '" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture" webkitAllowFullScreen mozallowfullscreen allowfullscreen></iframe></div></div>';

			/** Скачать и сохранить если сохраняется документ */
			$image = "https://img.youtube.com/vi/" . $match[0] . "/sddefault.jpg";
			@mkdir(MODX_BASE_PATH . $this->dir_images . $this->hosting . '/', 0755, true);
			$img_file = $this->dir_images . $this->hosting . '/' . $match[0] . '.jpg';
			if(is_file(MODX_BASE_PATH . $img_file)){
				$this->videoInfo['image'] = $img_file;
			}else{
				$this->videoInfo['image'] = $image;
			}
			if($this->autosave){
				$img = $this->fetchPage($image);
				@file_put_contents(MODX_BASE_PATH . $img_file, $img);
				if(is_file(MODX_BASE_PATH . $img_file)){
					$this->videoInfo['image'] = $img_file;
				}else{
					$this->videoInfo['image'] = $image;
				}
			}
		}else{
			return array();
		}
		return $this->videoInfo;
	}

	/** Скачивание с помощью CURL */
	private function fetchPage($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		return curl_exec($ch);
	}
	
	public function setLink(string $link = null)
	{
		$videoInfo = array();
		if (!empty($link)) {
			$videoInfo = $this->cleanLink($link)->getVideoInfo();
		}
		return $videoInfo;
	}
}