<?php
// This page is only for teachers, checked in index.php

$teacher_id = $_SESSION['user_id'];
$pre_selected_subject_id = $_GET['subject_id'] ?? null;

// Fetch subjects assigned to this teacher
$subjects_stmt = $pdo->prepare(
    "SELECT s.id, s.name FROM subjects s JOIN enrollments e ON s.id = e.subject_id WHERE e.user_id = :teacher_id GROUP BY s.id"
);
$subjects_stmt->execute(['teacher_id' => $teacher_id]);
$teacher_subjects = $subjects_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Sınav Oluştur - Sınav Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Yeni Sınav Oluştur</h1>
            <a href="index.php?page=exams<?php echo $pre_selected_subject_id ? '&subject_id=' . $pre_selected_subject_id : ''; ?>" class="btn btn-secondary">Sınav Listesine Dön</a>
        </div>

        <form action="src/teacher/exam_action.php" method="POST" enctype="multipart/form-data">
            <div class="accordion" id="examAccordion">

                <!-- Step 1: Subject and Exam Name -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            Adım 1: Ders Seçimi ve Sınav Adı
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#examAccordion">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label for="subject_id" class="form-label">Ders</label>
                                <?php if ($pre_selected_subject_id): ?>
                                    <input type="hidden" name="subject_id" id="subject_id" value="<?php echo $pre_selected_subject_id; ?>">
                                    <input type="text" class="form-control" value="<?php 
                                        $selected_subject_name = '';
                                        foreach ($teacher_subjects as $subject) {
                                            if ($subject['id'] == $pre_selected_subject_id) {
                                                $selected_subject_name = $subject['name'];
                                                break;
                                            }
                                        }
                                        echo htmlspecialchars($selected_subject_name);
                                    ?>" readonly>
                                <?php else: ?>
                                    <select class="form-select" id="subject_id" name="subject_id" required>
                                        <option value="">Lütfen bir ders seçin...</option>
                                        <?php foreach ($teacher_subjects as $subject): ?>
                                            <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Sınav Adı</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <button type="button" class="btn btn-primary" id="next_to_step2">İleri</button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Define Questions -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" disabled>
                            Adım 2: Soruları Tanımla
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#examAccordion">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label for="num_questions" class="form-label">Question Count</label>
                                <input type="number" class="form-control" id="num_questions" name="num_questions" min="1" value="1">
                            </div>
                            <div id="questions_table_container"></div>
                            <button type="button" class="btn btn-primary mt-3" id="next_to_step3">İleri</button>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Upload Exam File -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" disabled>
                            Adım 3: Sınav Dosyasını Yükle
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#examAccordion">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label class="form-label">Soru Dosyası</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="source_type" id="source_pdf" value="pdf" checked>
                                    <label class="form-check-label" for="source_pdf">PDF Yükle</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="source_type" id="source_url" value="url">
                                    <label class="form-check-label" for="source_url">URL ile Ekle</label>
                                </div>
                                <div id="pdf_upload_container">
                                    <input type="file" class="form-control" id="question_pdf" name="question_pdf" accept=".pdf">
                                </div>
                                <div id="url_input_container" style="display: none;">
                                    <input type="url" class="form-control" id="question_url" name="question_url" placeholder="https://example.com/questions.pdf">
                                </div>
                            </div>
                            <button type="submit" name="action" value="create_exam_with_questions" class="btn btn-primary">Sınavı Oluştur</button>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subjectSelect = document.getElementById('subject_id');
    const examNameInput = document.getElementById('name');
    const numQuestionsInput = document.getElementById('num_questions');
    const questionsTableContainer = document.getElementById('questions_table_container');
    let topics = [];

    const btnStep1 = document.querySelector('#headingOne button');
    const btnStep2 = document.querySelector('#headingTwo button');
    const btnStep3 = document.querySelector('#headingThree button');

    document.getElementById('next_to_step2').addEventListener('click', function() {
        if (subjectSelect.value && examNameInput.value) {
            btnStep2.disabled = false;
            btnStep2.click();
            fetchTopics(subjectSelect.value, generateQuestionTable);
        } else {
            alert('Lütfen ders seçip sınav adı giriniz.');
        }
    });

    document.getElementById('next_to_step3').addEventListener('click', function() {
        btnStep3.disabled = false;
        btnStep3.click();
    });

    numQuestionsInput.addEventListener('change', generateQuestionTable);

    function fetchTopics(subjectId, callback) {
        if (subjectId) {
            fetch(`src/get_topics.php?subject_id=${subjectId}`)
                .then(response => response.json())
                .then(data => {
                    topics = data;
                    if (callback) callback();
                });
        } else {
            topics = [];
            if (callback) callback();
        }
    }

    function generateQuestionTable() {
        const numQuestions = parseInt(numQuestionsInput.value) || 0;
        let tableHtml = `
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Soru No</th>
                        <th>Soru Tipi</th>
                        <th>Konu</th>
                        <th>Doğru Cevap</th>
                        <th>Puan</th>
                    </tr>
                </thead>
                <tbody>
        `;

        for (let i = 1; i <= numQuestions; i++) {
            tableHtml += `
                <tr>
                    <td>${i}<input type="hidden" name="questions[${i}][question_number]" value="${i}"></td>
                    <td>
                        <select class="form-select question-type-selector" name="questions[${i}][type]" data-question-index="${i}" required>
                            <option value="">Seçiniz...</option>
                            <option value="multiple_choice">Çoktan Seçmeli</option>
                            <option value="fill_in_the_blank">Boşluk Doldurma</option>
                            <option value="essay">Açık Uçlu (Essay)</option>
                            <option value="true_false">Doğru / Yanlış</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-select topic-select" name="questions[${i}][topic_id][]" data-question-index="${i}">
                            <option value="">Konu Seçin...</option>
                            ${topics.map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
                        </select>
                    </td>
                    <td class="answer-cell" id="answer_cell_${i}"></td>
                    <td><input type="number" class="form-control" name="questions[${i}][points]" min="1" value="10" required></td>
                </tr>
            `;
        }

        tableHtml += `</tbody></table>`;
        questionsTableContainer.innerHTML = tableHtml;
    }

    questionsTableContainer.addEventListener('change', function(e) {
        if (e.target.classList.contains('question-type-selector')) {
            const type = e.target.value;
            const index = e.target.dataset.questionIndex;
            const answerCell = document.getElementById(`answer_cell_${index}`);
            const topicSelect = document.querySelector(`select[name="questions[${index}][topic_id][]"]`);
            let html = '';

            if (type === 'essay') {
                topicSelect.multiple = true;
            } else {
                topicSelect.multiple = false;
            }

            switch (type) {
                case 'multiple_choice':
                    html = `
                        <select name="questions[${index}][answer]" class="form-select" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    `;
                    break;
                case 'true_false':
                    html = `
                        <div class="form-check"><input class="form-check-input" type="radio" name="questions[${index}][answer]" value="true" checked><label class="form-check-label">Doğru</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="questions[${index}][answer]" value="false"><label class="form-check-label">Yanlış</label></div>
                    `;
                    break;
                case 'fill_in_the_blank':
                    html = `<input type="text" name="questions[${index}][answer]" class="form-control" required>`;
                    break;
                case 'essay':
                    html = '<p class="text-muted">Manuel Değerlendirme</p>';
                    break;
            }
            answerCell.innerHTML = html;
        }
    });

    const sourcePdfRadio = document.getElementById('source_pdf');
    const sourceUrlRadio = document.getElementById('source_url');
    const pdfUploadContainer = document.getElementById('pdf_upload_container');
    const urlInputContainer = document.getElementById('url_input_container');

    sourcePdfRadio.addEventListener('change', function() {
        if (this.checked) {
            pdfUploadContainer.style.display = 'block';
            urlInputContainer.style.display = 'none';
        }
    });

    sourceUrlRadio.addEventListener('change', function() {
        if (this.checked) {
            pdfUploadContainer.style.display = 'none';
            urlInputContainer.style.display = 'block';
        }
    });

    // Pre-fetch topics if subject is pre-selected
    const preSelectedSubjectId = <?php echo json_encode($pre_selected_subject_id); ?>;
    if (preSelectedSubjectId) {
        subjectSelect.dispatchEvent(new Event('change'));
    }
});
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
