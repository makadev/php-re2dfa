<?php


namespace makadev\RE2DFA\CLI;


use Analog\Analog;
use Analog\Handler\Ignore;
use Analog\Handler\Multi;
use Analog\Handler\Stderr;
use Analog\Handler\Threshold;
use Analog\Logger;
use makadev\RE2DFA\FiniteAutomaton\DFA;
use makadev\RE2DFA\FiniteAutomaton\DFABuilder;
use makadev\RE2DFA\FiniteAutomaton\DFAMinimizer;
use makadev\RE2DFA\RegEx\RegExParser;
use makadev\RE2DFA\RegEx\RegExParserException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Main implements LoggerAwareInterface {

    protected LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function __construct() {
        $analogLogger = new Logger();
        // only output message
        $analogLogger->format('%4$s' . "\n");
        // log to STDERR, with log level notice (ignore info/debug)
        $analogLogger->handler(Threshold::init(Stderr::init(), Analog::NOTICE));
        $this->logger = $analogLogger;
    }

    protected string $inputFile = "";
    protected string $outputType = "json-tables";
    protected string $outFile = "";

    /**
     * @param array $options
     * @param string $short
     * @param string $long
     * @param null|string $default
     * @return null|string
     */
    protected function pickOption(array $options, string $short, string $long, $default = null) {
        $hasOpt = isset($options[$short]) || isset($options[$long]);
        if (!$hasOpt) return $default;
        // this prefers long options, meaning the following will prefer "template1": cli --file=template1 -f=template2
        $opt = isset($options[$long]) ? $options[$long] : $options[$short];
        if (is_array($opt)) {
            return end($opt);
        }
        return $opt;
    }

    protected function invalidCommand(string $message): void {
        $this->logger->error($message);

        $this->printHelp();

        exit(1);
    }

    protected function printHelp(): void {
        $script = '<binary>';
        global $argv;
        if (!empty($argv) && isset($argv[0])) {
            $script = $argv[0];
        }

        $this->logger->notice("Usage: " . $script . ' <Options>');
        $this->logger->notice('Options:');
        $this->logger->notice("  -h, --help");
        $this->logger->notice("    show this");
        $this->logger->notice("  -f, --file");
        $this->logger->notice("    Input file");
        $this->logger->notice("  -t, --type");
        $this->logger->notice("    Type of output to be generated");
        $this->logger->notice("      json-tables: generate JSON with the DFA Tables (default)");
        $this->logger->notice("      php-tables: generate a PHP Script with the DFA Tables");
        $this->logger->notice("      dot-graph: generate Graphviz DOT file representing the DFA as Graph");
        $this->logger->notice("  -o, --output");
        $this->logger->notice("    Output file");
        $this->logger->notice("  -v, --verbose");
        $this->logger->notice("    Output more info");
    }

    protected function handleOpts(): void {
        $shortOpts = "";
        $longOpts = [];

        $shortOpts .= "h";
        $longOpts[] = "help";

        $shortOpts .= "v";
        $longOpts[] = "verbose";

        $shortOpts .= "f:";
        $longOpts[] = "file:";

        $shortOpts .= "t:";
        $longOpts[] = "type:";

        $shortOpts .= "o::";
        $shortOpts .= "output::";

        $options = getopt($shortOpts, $longOpts);

        if ($this->pickOption($options, "h", "help") === false) {
            $this->printHelp();
            exit(0);
        }

        if ($this->pickOption($options, "v", "verbose") === false) {
            if ($this->logger instanceof Logger) {
                $this->logger->handler(Threshold::init(Stderr::init(), Analog::INFO));
            } else {
                throw new RuntimeException("Unknown Logger installed");
            }
        }

        $this->inputFile = $this->pickOption($options, "f", "file", '');
        if (strlen($this->inputFile) <= 0) {
            $this->invalidCommand("No input file given.");
        }
        if (!file_exists($this->inputFile)) {
            $this->invalidCommand("Given input file does not exist: " . $this->inputFile);
        }

        $this->outputType = $this->pickOption($options, "t", "type", $this->outputType);
        if (!in_array($this->outputType, [
            'dot-graph',
            'json-tables',
            'php-tables',
        ])) {
            $this->invalidCommand("Unknown output type: " . $this->outputType);
        }

        $outFile = $this->pickOption($options, "t", "template", $this->outFile);
        if ($outFile === false || strlen($outFile) <= 0) {
            $this->outFile = "";
        } else {
            if (file_exists($this->outFile)) {
                $this->invalidCommand("Output file already exist: " . $this->outFile);
            }
        }
    }

    protected function readDFASpec(): DFA {
        $reader = new InputReader($this->inputFile);
        $dfaBuilder = new DFABuilder();
        $token = $reader->nextToken();
        while ($token->type === "ID") {
            $id = $token->content;
            $token = $reader->nextToken();
            if ($token->type === "DELIMITER") {
                $token = $reader->nextToken();
                if ($token->type === "REGEX") {
                    $regex = $token->content;
                    $this->logger->info("Processing: " . $id);
                    $rp = new RegExParser($regex);
                    $enfa = null;
                    try {
                        $enfa = $rp->build();
                    } catch (RegExParserException $rex) {
                        $this->logger->error("Regex Parsing failed for: " . $regex);
                        $this->logger->error("With error: " . $rex->getMessage());
                    }
                    $dfaBuilder->addENFA($enfa, $id);
                    $token = $reader->nextToken();
                }
            }
        }
        if ($token->type !== "EOF") {
            $this->logger->error("Parsing specification failed with error: " . $token->content);
            exit(2);
        }

        $this->logger->info("Build DFA");
        $dfa = $dfaBuilder->build();

        $this->logger->info("Minimize DFA");
        $minimizer = new DFAMinimizer($dfa, true);
        $mdfa = $minimizer->minimize();

        return $mdfa;
    }

    protected function writeOutput(DFA $dfa) {
        $this->logger->info("Writing output");
        $output = null;
        switch ($this->outputType) {
            case "dot-graph":
                $output = new OutputGraphWriterDOT($dfa);
                break;
            case "php-tables":
                $output = new OutputTableWriterPHP($dfa);
                break;
            default:
                $output = new OutputTableWriterJSON($dfa);
        }
        if ($this->outFile === "") {
            $output->print();
        } else {
            $output->write($this->outFile);
        }
    }

    public function run(): int {
        $this->handleOpts();

        $dfa = $this->readDFASpec();

        $this->writeOutput($dfa);

        return 0;
    }

}