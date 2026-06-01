document.addEventListener('DOMContentLoaded', function () {
    const progressCircle = document.getElementById('progressCircle');
    if (progressCircle) {
        const progress = parseInt(progressCircle.dataset.progress);
        const circle = document.getElementById('progressPath');
        const radius = 15.9155;
        const circumference = 2 * Math.PI * radius;

        setTimeout(() => {
            const dashArray = `${(progress * circumference) / 100}, ${circumference}`;
            circle.style.strokeDasharray = dashArray;
            progressCircle.querySelector('.progress-text').textContent = progress + '%';
        }, 300);
    }

    const startForm = document.getElementById('startCourseForm');
    if (startForm) {
        startForm.addEventListener('submit', function (e) {
            const button = this.querySelector('.start-course-btn');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Начинаем...</span>';

            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-check"></i><span>Курс начат!</span>';
                setTimeout(() => {
                    window.location.href = '{{ route("course.learn", $course->url) }}';
                }, 1000);
            }, 1500);
        });
    }

    const modal = document.querySelector('.course-already-started-modal');
    if (modal) {
        const closeBtn = modal.querySelector('.modal-close');
        const restartBtn = modal.querySelector('.restart-course-btn');
        const overlay = modal.querySelector('.modal-overlay');

        const closeModal = () => {
            modal.style.opacity = '0';
            setTimeout(() => modal.remove(), 300);
        };

        closeBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);

        restartBtn.addEventListener('click', function () {
            if (confirm('Вы уверены, что хотите начать курс заново? Весь текущий прогресс будет сброшен.')) {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сбрасываем...';

                fetch('{{ route("course.restart", $course->url) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                }).then(response => response.json())
                    .then(data => {
                        if (data.status) {
                            window.location.reload();
                        }
                    });
            }
        });
    }
});
