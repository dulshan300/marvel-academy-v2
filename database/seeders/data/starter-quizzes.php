<?php

/*
|--------------------------------------------------------------------------
| Starter Quizzes
|--------------------------------------------------------------------------
|
| One real multiple-choice quiz for the first module of each course, keyed
| by "{key stage}.{subject area}". Each question lists its options with the
| correct answer first; the seeder shuffles them.
|
*/

return [
    'ks3.Mathematics' => [
        ['What is 3/4 as a percentage?', ['75%', '34%', '43%', '0.75%'], 'Divide 3 by 4 to get 0.75, then multiply by 100.'],
        ['Which of these is a prime number?', ['29', '27', '21', '33'], '29 has exactly two factors: 1 and itself.'],
        ['What is −7 + 12?', ['5', '−19', '19', '−5'], 'Start at −7 and move 12 places up the number line.'],
        ['Round 4.678 to 1 decimal place.', ['4.7', '4.6', '4.68', '5.0'], 'The second decimal digit is 7, so round the first decimal up.'],
        ['What is the lowest common multiple of 4 and 6?', ['12', '24', '2', '10'], '12 is the smallest number in both times tables.'],
    ],
    'ks3.Biology' => [
        ['Which organelle controls the activities of a cell?', ['Nucleus', 'Cell membrane', 'Cytoplasm', 'Ribosome'], 'The nucleus contains the genetic material that controls the cell.'],
        ['Which structure is found in plant cells but not animal cells?', ['Cell wall', 'Nucleus', 'Cell membrane', 'Mitochondria'], 'Plant cells have a cellulose cell wall for support.'],
        ['Where does aerobic respiration mainly take place?', ['Mitochondria', 'Chloroplasts', 'Vacuole', 'Nucleus'], 'Mitochondria release energy from glucose using oxygen.'],
        ['What is a group of similar cells working together called?', ['A tissue', 'An organ', 'An organism', 'A system'], 'Cells form tissues, tissues form organs, organs form systems.'],
        ['What does the cell membrane do?', ['Controls what enters and leaves the cell', 'Makes proteins', 'Stores sap', 'Absorbs light'], 'The membrane is partially permeable.'],
    ],
    'ks3.Chemistry' => [
        ['What is the smallest particle of an element?', ['An atom', 'A molecule', 'A compound', 'A mixture'], 'Atoms are the building blocks of elements.'],
        ['Which of these is a compound?', ['Water (H₂O)', 'Oxygen (O₂)', 'Iron (Fe)', 'Helium (He)'], 'A compound contains two or more different elements chemically joined.'],
        ['What is the chemical symbol for sodium?', ['Na', 'S', 'So', 'Sd'], 'Na comes from the Latin name natrium.'],
        ['How many elements are in carbon dioxide (CO₂)?', ['2', '3', '1', '4'], 'Carbon and oxygen: two different elements.'],
        ['Elements in the same vertical column of the periodic table are called a…', ['Group', 'Period', 'Family tree', 'Series'], 'Rows are periods; columns are groups.'],
    ],
    'ks3.Physics' => [
        ['What is the unit of speed?', ['m/s', 'N', 'kg', 'J'], 'Speed is distance (m) divided by time (s).'],
        ['A car travels 100 m in 5 s. What is its speed?', ['20 m/s', '500 m/s', '0.05 m/s', '95 m/s'], 'Speed = distance ÷ time = 100 ÷ 5.'],
        ['Which instrument measures time for a speed experiment?', ['Stopwatch', 'Ammeter', 'Newton meter', 'Thermometer'], 'Time is measured with a stopwatch or light gates.'],
        ['On a distance–time graph, a horizontal line means the object is…', ['Stationary', 'Accelerating', 'Moving at a steady speed', 'Decelerating'], 'Distance is not changing, so the object is not moving.'],
        ['What is relative motion?', ['Motion measured compared with another moving object', 'Motion in a circle', 'Motion caused by gravity', 'Motion at the speed of light'], 'Speed depends on the frame of reference.'],
    ],
    'gcse.Mathematics' => [
        ['Expand and simplify 3(x + 4) − 2(x − 1).', ['x + 14', 'x + 10', '5x + 14', 'x + 11'], '3x + 12 − 2x + 2 = x + 14.'],
        ['Factorise fully 6x² + 9x.', ['3x(2x + 3)', '3(2x² + 3x)', 'x(6x + 9)', '6x(x + 9)'], 'The highest common factor is 3x.'],
        ['Solve 5x − 7 = 18.', ['x = 5', 'x = 2.2', 'x = 25', 'x = 11'], 'Add 7 to get 5x = 25, then divide by 5.'],
        ['Factorise x² + 5x + 6.', ['(x + 2)(x + 3)', '(x + 1)(x + 6)', '(x − 2)(x − 3)', '(x + 5)(x + 1)'], 'Find two numbers that multiply to 6 and add to 5.'],
        ['Solve the simultaneous equations x + y = 10 and x − y = 4.', ['x = 7, y = 3', 'x = 3, y = 7', 'x = 6, y = 4', 'x = 14, y = −4'], 'Adding the equations gives 2x = 14.'],
    ],
    'gcse.Biology' => [
        ['Which type of cell has no nucleus?', ['Prokaryotic cell', 'Eukaryotic cell', 'Plant cell', 'Animal cell'], 'Bacteria are prokaryotes; their DNA floats in the cytoplasm.'],
        ['What is the function of ribosomes?', ['Protein synthesis', 'Photosynthesis', 'Respiration', 'Cell division'], 'Ribosomes join amino acids to make proteins.'],
        ['A cell is 0.02 mm across and its image is 10 mm. What is the magnification?', ['×500', '×200', '×0.002', '×50'], 'Magnification = image size ÷ actual size.'],
        ['Which process moves substances from high to low concentration without energy?', ['Diffusion', 'Active transport', 'Osmosis against a gradient', 'Mitosis'], 'Diffusion is passive.'],
        ['What do chloroplasts contain to absorb light?', ['Chlorophyll', 'Glucose', 'Cellulose', 'Haemoglobin'], 'Chlorophyll absorbs light for photosynthesis.'],
    ],
    'gcse.Chemistry' => [
        ['What is the relative charge of a neutron?', ['0', '+1', '−1', '+2'], 'Neutrons are neutral.'],
        ['An atom has 11 protons and 12 neutrons. What is its mass number?', ['23', '11', '12', '1'], 'Mass number = protons + neutrons.'],
        ['Isotopes of an element have the same number of…', ['Protons', 'Neutrons', 'Nucleons', 'Mass number'], 'Isotopes differ only in their number of neutrons.'],
        ['What is the electronic structure of sodium (atomic number 11)?', ['2,8,1', '2,8,2', '2,9', '8,2,1'], 'Shells fill 2, then 8, then the rest.'],
        ['Who discovered the electron, leading to the plum pudding model?', ['J.J. Thomson', 'Ernest Rutherford', 'Niels Bohr', 'John Dalton'], 'Thomson discovered the electron in 1897.'],
    ],
    'gcse.Physics' => [
        ['What is the equation linking potential difference, current and resistance?', ['V = I × R', 'V = I ÷ R', 'R = V × I', 'I = V × R'], 'Ohm’s law: V = IR.'],
        ['A 2 A current flows through a 6 Ω resistor. What is the potential difference?', ['12 V', '3 V', '8 V', '0.33 V'], 'V = 2 × 6 = 12 V.'],
        ['In a series circuit, the current is…', ['The same everywhere', 'Shared between branches', 'Zero across resistors', 'Highest at the battery'], 'There is only one path for charge.'],
        ['What happens to total resistance when resistors are added in parallel?', ['It decreases', 'It increases', 'It stays the same', 'It doubles'], 'More paths make it easier for charge to flow.'],
        ['What is the unit of charge?', ['Coulomb (C)', 'Ampere (A)', 'Volt (V)', 'Ohm (Ω)'], 'Charge = current × time, measured in coulombs.'],
    ],
];
