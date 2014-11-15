<?php
namespace wapmorgan\TimeParser;

class DebugStream {
	static public function show($message) {
		if (TimeParser::debugging()) echo $message;
	}
}
