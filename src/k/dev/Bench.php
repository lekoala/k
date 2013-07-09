<?php

namespace k\dev;

use \ReflectionClass;

/**
 * Bench
 *
 * @author lekoala
 */
class Bench {

	protected $rounds = 10000;
	protected $currentRound = 0;

	public function getRounds() {
		return $this->rounds;
	}

	public function setRounds($rounds) {
		$this->rounds = $rounds;
		return $this;
	}

	public function run($rounds = null, $intro = null) {
		set_time_limit(0);
		if ($rounds === null) {
			$rounds = $this->rounds;
		}
		$allTests = [];
		$refl = new ReflectionClass(get_called_class());
		$methods = $refl->getMethods(\ReflectionMethod::IS_PROTECTED);
		
		echo "Starting $rounds rounds of test";
		if($intro) {
			echo $intro;
		}
		echo "<hr/>";
		
		foreach ($methods as $method) {
			$name = $method->getName();

			$loopsTime = [];
			$testStartTime = microtime(true);
			$testStartMemory = memory_get_usage();
			for ($i = 0; $i < $rounds; ) {
				$this->currentRound = ++$i;
				$loopStart = microtime(true);
				$content = $this->$name();
				$loopsTime[] = (microtime(true) - $loopStart) * 1000;
			}
			$time = (microtime(true) - $testStartTime) * 1000;
			$memory = memory_get_usage() - $testStartMemory;
			
			$max = max($loopsTime);
			$min = min($loopsTime);
			$avg = array_sum($loopsTime) / count($loopsTime);

			array_push($allTests, compact('name','time','memory','max','min','avg'));

			printf("<div class='test'><table class='table test-table'><thead>
			<tr><th colspan='5'>Test '%s'</th></tr>
			<tr><td>Time</td><td>Max</td><td>Min</td><td>Avg</td><td>Mem</td></tr></thead>
			<tbody>
			<tr><td>%0.4f</td><td>%0.4f</td><td>%0.4f</td><td>%0.4f</td><td>%0.2f kb</td></tr>
			</tbody>
		</table><div class='code'>%s</div></div>", $name, $time, $max, $min, $avg,$memory / 1024,print_r($content,true));
		}

		//summary
		usort($allTests, function($a, $b) {
					return ($a['time'] > $b['time']) ? 1 : -1;
				});

		$base = $allTests[0]['time'];

		printf("<hr><table class='table summary-table'><thead>
			<tr><th colspan='3'>Summary</th></tr>
			<tr><td>Test</td><td>Time</td><td>Speed</td></tr></thead><tbody>");
		foreach ($allTests as $t) {
			$perc = round($t['time'] / $base * 100) . ' %';
			printf('<tr><td>%s</td><td>%0.4f</td><td>%s</td></tr>', $t['name'], $t['time'], $perc);
		}
		printf("</tbody></table>");
	}

}