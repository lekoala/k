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
			$testStart = microtime(true);
			for ($i = 0; $i < $rounds; $i++) {
				$this->currentRound = $i;
				$loopStart = microtime(true);
				$this->$name();
				$loopsTime[] = (microtime(true) - $loopStart) * 1000;
			}
			$testTime = (microtime(true) - $testStart) * 1000;

			$maxTime = max($loopsTime);
			$minTime = min($loopsTime);
			$avgTime = array_sum($loopsTime) / count($loopsTime);

			array_push($allTests, ['name' => $name, 'time' => $testTime]);

			printf("<table class='table test-table'><thead>
			<tr><th colspan='4'>Test '%s'</th></tr>
			<tr><td>Time</td><td>Max</td><td>Min</td><td>Avg</td></tr></thead>
			<tbody>
			<tr><td>%0.6f</td><td>%0.6f</td><td>%0.6f</td><td>%0.6f</td></tr>
			</tbody>
		</table>", $name, $testTime, $maxTime, $minTime, $avgTime);
		}

		//summary
		usort($allTests, function($a, $b) {
					return ($a['time'] > $b['time']) ? 1 : -1;
				});

		$base = $allTests[0]['time'];

		printf("<table class='table summary-table'><thead>
			<tr><th colspan='3'>Summary</th></tr>
			<tr><td>Test</td><td>Time</td><td>Percentage</td></tr></thead><tbody>");
		foreach ($allTests as $t) {
			$perc = round($t['time'] / $base * 100) . ' %';
			printf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>', $t['name'], $t['time'], $perc);
		}
		printf("</tbody></table>");
	}

}