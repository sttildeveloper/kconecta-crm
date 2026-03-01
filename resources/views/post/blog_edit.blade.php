@extends('layouts.backoffice')

@section('title', 'Kconecta - Editar articulo')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/app/blog_create_modal.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css" rel="stylesheet">
@endsection

@section('heading')
    Editar art&iacute;culo
@endsection

@section('subheading')
    Actualiza el contenido del blog
@endsection

@section('header_actions')
    <a class="secondary" href="{{ url('/post/blogs') }}">Volver al listado</a>
@endsection

@section('content')
    <div class="container-main">
        <div class="form-container">
            <h2>Editar art&iacute;culo</h2>
            <form id="blogPostForm" method="POST" action="{{ url('/post/blogs/update/' . $post['id']) }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="title">Titulo del articulo</label>
                    <input type="text" id="title" name="title" value="{{ $post['title'] }}" placeholder="Introduce el titulo del blog" required>
                </div>

                <div class="form-group">
                    <label for="slug">Slug (URL amigable)</label>
                    <input type="text" id="slug" name="slug" value="{{ $post['slug'] }}" placeholder="ej: mi-primer-articulo-de-blog" required>
                </div>

                <div class="form-group">
                    <label for="summary">Resumen del articulo</label>
                    <textarea id="summary" name="summary" placeholder="Escribe un breve resumen..." rows="4" required>{{ $post['summary'] }}</textarea>
                </div>

                <div class="form-group">
                    <label for="featured_image">Imagen destacada</label>
                    <input type="file" id="featured_image" name="featured_image" accept="image/*">
                    <div class="image-preview-container">
                        @php
                            $imageUrl = $post['featured_image'] ? asset($post['featured_image']) : '';
                        @endphp
                        <img id="imagePreview" src="{{ $imageUrl }}" alt="Previsualizacion de imagen" style="{{ $imageUrl ? '' : 'display: none;' }}">
                        <span id="imagePlaceholder" class="placeholder-text" style="{{ $imageUrl ? 'display: none;' : '' }}">No hay imagen seleccionada</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category">Categoria</label>
                    <select id="category" name="category">
                        <option value="">Selecciona una categoria</option>
                        @foreach ($categories as $categoryId => $categoryLabel)
                            <option value="{{ $categoryId }}" {{ (int) $post['category_id'] === (int) $categoryId ? 'selected' : '' }}>
                                {{ $categoryLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="summernote"></div>

                <button type="submit" class="submit-button">Guardar cambios</button>
            </form>
            <div id="feedbackMessage" class="feedback-message"></div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js"></script>
    <script>
        $('#summernote').summernote({
            placeholder: 'Escribe el contenido del articulo...',
            tabsize: 2,
            height: 240,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

        $('#summernote').summernote('code', @json($post['content']));

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('blogPostForm');
            const feedbackMessage = document.getElementById('feedbackMessage');
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            const featuredImageInput = document.getElementById('featured_image');
            const imagePreview = document.getElementById('imagePreview');
            const imagePlaceholder = document.getElementById('imagePlaceholder');

            titleInput.addEventListener('input', () => {
                const title = titleInput.value;
                const slug = title
                    .toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
            });

            featuredImageInput.addEventListener('change', (event) => {
                const file = event.target.files[0];

                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                        imagePlaceholder.style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                } else if (!imagePreview.src) {
                    imagePreview.src = '#';
                    imagePreview.style.display = 'none';
                    imagePlaceholder.style.display = 'block';
                }
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const editorElement = document.getElementById('summernote');
                const editorContent = $(editorElement).summernote('code');
                const formData = new FormData(form);
                formData.append('content', editorContent);

                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }
                feedbackMessage.style.display = 'none';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name=\"_token\"]').value,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    const data = await response.json();

                    if (!response.ok || data.status !== 'success') {
                        throw new Error(data.message || 'No se pudo guardar el articulo.');
                    }

                    window.location.href = '/post/blogs';
                } catch (error) {
                    feedbackMessage.textContent = error.message || 'Error al guardar el articulo.';
                    feedbackMessage.style.backgroundColor = '#ffe6e6';
                    feedbackMessage.style.color = '#5e1a1a';
                    feedbackMessage.style.display = 'block';
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                }
            });
        });
    </script>
@endsection
