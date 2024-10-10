<?php

namespace Ginov\CaldavPlugs\Dto;

class CalendarCalDAV
{
	private string $url;
	private string $displayname;
	private string $ctag;

	private ?string $calendar_id;

	private string $rbg_color;

	private int $order;

	private string $description;

	private string $timeZone;

	function __construct(string $displayname, string $timeZone = 'Europe/Paris')
	{
		// $this->calendar_id = md5(time().$displayname);
		$this->calendar_id = null;
		$this->displayname = $displayname;
		$this->description = $displayname;
		$this->timeZone = $timeZone;
		$this->url = md5(time());
		$this->ctag = '';
		$this->rbg_color = '';
	}

	function __toString()
	{
		return ('(URL: ' . $this->url . '   Ctag: ' . $this->ctag . '   Displayname: ' . $this->displayname . ')' . "\n");
	}

	// Getters

	function getURL(): string
	{
		return $this->url;
	}

	function getDisplayName(): string
	{
		return $this->displayname;
	}

	function getCTag(): string
	{
		return $this->ctag;
	}

	function getCalendarID(): ?string
	{
		return $this->calendar_id;
	}

	function getRBGcolor(): ?string
	{
		return $this->rbg_color;
	}

	function getOrder(): int
	{
		return $this->order;
	}


	// Setters

	function setURL($url): self
	{
		$this->url = $url;
		return $this;
	}

	function setDisplayName($displayname): self
	{
		$this->displayname = $displayname;
		return $this;
	}

	function setCtag($ctag): self
	{
		$this->ctag = $ctag;
		return $this;
	}

	function setCalendarID(?string $calendar_id): self
	{
		$this->calendar_id = $calendar_id;
		return $this;
	}

	function setRBGcolor(?string $rbg_color): self
	{
		$this->rbg_color = $rbg_color;
		return $this;
	}

	function setOrder(int $order): self
	{
		$this->order = $order;
		return $this;
	}

	public function setTimeZone(string $timeZone): self
	{
		$this->timeZone = $timeZone;
		return $this;
	}

	public function getTimeZone(): string
	{
		return $this->timeZone;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): self
	{
		$this->description = $description ? $description : $this->displayname;
		return $this;
	}
}
