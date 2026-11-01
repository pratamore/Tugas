            </main>
            <footer class="app-footer text-center py-3">
                <small>&copy; <?= date('Y') ?> Jejak Rasa - Coffee &amp; Eatery</small>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(function_exists('asset_url') ? asset_url('assets/js/app.js') : (isset($base) ? $base : '') . '/assets/js/app.js') ?>"></script>
</body>
</html>
