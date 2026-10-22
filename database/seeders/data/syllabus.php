<?php

/*
|--------------------------------------------------------------------------
| Marvel Academy Syllabus
|--------------------------------------------------------------------------
|
| Monthly teaching modules per subject and year group, September to April,
| taken from the existing marvelacademy.org.uk course pages. May to August
| are revision and look-ahead months.
|
*/

return [
    'ks3' => [
        'Mathematics' => [
            7 => ['Number I', 'Algebra I', 'Algebra II', 'Ratio I', 'Measure & Shapes I', 'Geometry I', 'Data Collection & Statistics I', 'Probability I'],
            8 => ['Number II', 'Algebra I', 'Algebra II', 'Ratio II', 'Measure & Shapes II', 'Geometry II', 'Data Collection & Statistics II', 'Probability II'],
            9 => ['Number I & II', 'Algebra I', 'Algebra II', 'Ratio I & II', 'Measure & Shapes I & II', 'Geometry I & II', 'Data Collection & Statistics I & II', 'Probability I & II'],
        ],
        'Biology' => [
            7 => ['Cells & Organisation', 'Skeleton & Muscular Systems', 'Reproduction', 'Inheritance & DNA', 'Relationships in an Ecosystem', 'Sense Organs (Ear, Eye & Nerves)', 'Respiration', 'Photosynthesis'],
            8 => ['Nutrition & Digestion', 'Gas Exchange', 'Photosynthesis', 'Respiration', 'Health & Disease', 'Circulatory System', 'Kidneys & Excretion', 'Classification, Evolution & Natural Selection'],
            9 => ['Cells, Organs, Nutrition & Digestion', 'Health & Disease', 'Inheritance & DNA', 'Reproduction', 'Gas Exchange', 'Skeleton & Muscular System', 'Respiration & Photosynthesis', 'Ecosystems'],
        ],
        'Chemistry' => [
            7 => ['Atoms, Elements & Compounds', 'Particulate Nature of Matter', 'Pure & Impure Substances', 'Chemical Reactions I', 'Chemical Reactions II', 'Introduction to the Periodic Table', 'Using Resources', 'Organic Chemistry I'],
            8 => ['The Periodic Table: Part 1', 'The Periodic Table: Part 2', 'Energetics', 'Materials', 'Earth & Atmosphere', 'Particulate Nature of Matter', 'Pure & Impure Substances', 'Organic Chemistry II'],
            9 => ['Energetics', 'Pure & Impure Substances', 'Chemical Reactions I & II', 'Periodic Table I', 'Periodic Table II', 'Earth & Atmosphere', 'Using Resources', 'Organic Chemistry'],
        ],
        'Physics' => [
            7 => ['Speed', 'Gravity', 'Energy Costs & Resources', 'Energy Transfer', 'Voltage & Resistance', 'Current', 'Sound', 'Light'],
            8 => ['Forces, Energy & Elasticity', 'Space', 'Pressure (Fluids & Atmosphere)', 'Work Done (Heating & Cooling)', 'Magnetism', 'Electromagnets', 'Waves I', 'Waves II'],
            9 => ['Forces', 'Gravity & Space', 'Energy I', 'Energy II', 'Electricity', 'Magnetism & Electromagnetism', 'Waves (Sound & Light)', 'Waves I & II'],
        ],
    ],
    'gcse' => [
        'Mathematics' => [
            10 => [
                'Expanding & Factorising, Linear and Simultaneous Equations, Algebraic Expressions & Fractions',
                'Fractions, Decimals & Percentages, Laws of Indices & Surds, Standard Form, Rounding & Significant Figures',
                'Inequalities, Primes, Factors & Multiples, Estimation & Bounds',
                'Linear Simultaneous Equations, Solving Quadratics by Factorising & the Quadratic Formula',
                'Straight-Line Equations, Linear Inequalities, Functions & Sequences',
                'Ratio, Gradients & Rates of Change, Percentages & Interest, Direct & Inverse Proportion',
                'Angles, Lines & Polygons, Area & Perimeter, Volume & Surface Area',
                'Loci & Constructions, Transformations, Congruence, Similar Shapes & Sampling',
            ],
            11 => [
                'Surds, Laws of Indices & Standard Form',
                'Bounds, Algebraic Fractions & Proof, Quadratic Simultaneous Equations, Straight-Line Graphs',
                'Curved Graphs, Graph Transformations, Sequences, Arcs & Sectors, Bearings',
                'Circle Theorems, Compound Measures & Rates of Flow, Similar Shapes',
                'Right-Angled Trigonometry, 3D Pythagoras & Vectors',
                'Volume & Surface Area, Histograms, Frequency Tables & Polygons',
                'Probability & Box Plots',
                'Simultaneous & Quadratic Equations Review',
            ],
        ],
        'Biology' => [
            10 => ['Cell Biology', 'Cardiovascular System', 'Lungs, Cell Transport, Mitosis & Stem Cells', 'Pathogens', 'Plant Biology', 'Enzymes, Food & Feeding the Human Race', 'Introduction to Genetics', 'Introduction to Ecology'],
            11 => ['Homeostasis', 'Nervous System', 'Genetics', 'Variation, Selective Breeding, Genetic Engineering & Cloning', 'Evolution & Classification', 'Ecology', 'Food Security, Human Impact & Material Cycling', 'Pathogens Review'],
        ],
        'Chemistry' => [
            10 => ['Atomic Structure', 'Periodic Table: Groups 1 & 7', 'Reactions of Acids & Bases', 'Rates of Reaction, Endothermic & Exothermic Reactions', 'Electrolysis', 'Reactivity Series & Displacement Reactions', 'Separation, Purification & Extraction of Metals', 'Chemistry of the Atmosphere & Earth Science'],
            11 => ['Rates of Reaction & Energy Changes', 'Organic Chemistry I', 'Quantitative Chemistry', 'Chemical Analysis', 'Reversible Reactions & Le Chatelier’s Principle', 'Organic Chemistry II (Carboxylic Acids, Esters & Polymers)', 'Chemistry of the Atmosphere', 'Electrolysis Review'],
        ],
        'Physics' => [
            10 => ['Electricity I (Current, Potential Difference, Resistance & Circuits)', 'Electricity II (Mains, Static & Electric Fields)', 'Energy Changes in a System', 'Conservation of Energy', 'Particle Model & Changes of State', 'Internal Energy & Specific Latent Heat', 'Atomic Structure', 'Nuclear Radiation, Half-Life, Decay & Fusion'],
            11 => ['Motion & Newton’s Laws', 'Forces, Elasticity & Moments', 'Pressure in Liquids', 'Waves I', 'Waves II', 'Magnetism & Electromagnetism', 'Space Physics', 'Momentum'],
        ],
    ],
];
