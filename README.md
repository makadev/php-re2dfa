## php-re2dfa
PHP Library for Regular Expression to DFA transformation.

### What it is
A Library for transforming [Regular Expressions](https://en.wikipedia.org/wiki/Regular_expression#Formal_language_theory) (RegEx)
into [Nondeterministic Finitie Automaton with ɛ-transitions](https://en.wikipedia.org/wiki/Nondeterministic_finite_automaton#NFA_with_%CE%B5-moves) (ɛ-NFA) and
[Deterministic Finitie Automaton](https://en.wikipedia.org/wiki/Deterministic_finite_automaton) (DFA) with named final states for the purpose of 
analyzing and further use the constructed DFA / ɛ-NFA for example in visualizations or to build tokenizer.

### What it's not
* PCRE compatible
* a fast Regular Expression matching alternative

### Features
* RegEx to ɛ-NFA transformation
* direct ɛ-NFA construction
* transform multiple ɛ-NFA with separate finitie states into a DFA
* ɛ-NFA simulation
* DFA simulation

### planned Features
* DFA minimization (f.e. Moore's partition algorithm)
* DFA and ɛ-NFA output (f.e. dot, json, text)

### internal TODOs
* add usage examples for the features
* optimizations here and there
* replace stack oriented RegExParser with AST
  * character classes like whitespace (like `:whitespace:`)
  * repetition expression (like `RE{min,max}`, `RE{exact}`)

