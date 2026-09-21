import React from 'react';

/**
 * ProgressTracker component showing authoritative backend progress and active stages.
 */
export default function ProgressTracker({ production }) {
  const percentage = Number(production?.progress_percentage ?? 0);
  const stages = production?.stages || [];
  const currentStage = production?.current_stage;

  return (
    <div className="portal-card">
      <div className="portal-card-header">
        <span className="portal-card-title">Progres Pengerjaan</span>
        {currentStage && (
          <span style={{ fontSize: '0.75rem', color: '#b45309', fontWeight: 600 }}>
            Saat ini: {currentStage}
          </span>
        )}
      </div>

      <div className="progress-bar-container">
        <div className="progress-header">
          <span className="progress-label">Tahapan Selesai</span>
          <span className="progress-val">{percentage}%</span>
        </div>
        <div className="progress-bar-bg">
          <div className="progress-bar-fill" style={{ width: `${percentage}%` }} />
        </div>
      </div>

      {stages.length === 0 ? (
        <p style={{ fontSize: '0.8125rem', color: '#64748b', textAlign: 'center', margin: '1rem 0' }}>
          Jadwal tahapan produksi akan ditampilkan saat pesanan mulai diproses di workshop.
        </p>
      ) : (
        <div className="stages-list">
          {stages.map((stage, idx) => {
            const isCompleted = stage.status === 'COMPLETED';
            const isInProgress = stage.status === 'IN_PROGRESS';
            const isLast = idx === stages.length - 1;

            let statusClass = 'pending';
            if (isCompleted) statusClass = 'completed';
            else if (isInProgress) statusClass = 'in_progress';

            const timeStr = isCompleted && stage.completed_at
              ? new Date(stage.completed_at).toLocaleDateString('id-ID', {
                  day: 'numeric',
                  month: 'short',
                  hour: '2-digit',
                  minute: '2-digit',
                })
              : isInProgress && stage.started_at
              ? `Mulai ${new Date(stage.started_at).toLocaleDateString('id-ID', {
                  day: 'numeric',
                  month: 'short',
                })}`
              : null;

            return (
              <div key={stage.sequence} className="stage-item">
                <div className="stage-track">
                  <div className={`stage-dot ${statusClass}`}>
                    {isCompleted ? '✓' : stage.sequence}
                  </div>
                  {!isLast && <div className={`stage-line ${isCompleted ? 'completed' : ''}`} />}
                </div>
                <div className="stage-details">
                  <div className="stage-name">
                    <span>{stage.name}</span>
                    <span className={`stage-badge ${statusClass}`}>{stage.status_label}</span>
                  </div>
                  {timeStr && <div className="stage-time">{timeStr}</div>}
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
