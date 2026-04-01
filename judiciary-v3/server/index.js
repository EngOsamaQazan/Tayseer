require('dotenv').config({ path: require('path').join(__dirname, '..', '.env') });

const express = require('express');
const cors = require('cors');
const path = require('path');
const multer = require('multer');

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, path.join(__dirname, '..', 'uploads'));
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1e9);
    const ext = path.extname(file.originalname);
    cb(null, file.fieldname + '-' + uniqueSuffix + ext);
  },
});
const upload = multer({ storage });
app.locals.upload = upload;

app.use('/uploads', express.static(path.join(__dirname, '..', 'uploads')));

app.use('/api/lookups', require('./routes/lookups'));
app.use('/api/cases', require('./routes/cases'));
app.use('/api/actions', require('./routes/actions'));
app.use('/api/persistence', require('./routes/persistence'));
app.use('/api/legal', require('./routes/legal'));
app.use('/api/collection', require('./routes/collection'));
app.use('/api/deadlines', require('./routes/deadlines'));
app.use('/api/correspondence', require('./routes/correspondence'));
app.use('/api/assets', require('./routes/assets'));
app.use('/api/stats', require('./routes/stats'));
app.use('/api/exports', require('./routes/exports'));
app.use('/api/batch', require('./routes/batch'));
app.use('/api/timeline', require('./routes/timeline'));

app.use((err, req, res, _next) => {
  console.error('Unhandled error:', err);
  res.status(500).json({ success: false, error: err.message || 'Internal server error' });
});

app.listen(PORT, () => {
  console.log(`Judiciary API running on http://localhost:${PORT}`);
});
